<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Note bodies are rich text stored as HTML. Everything that comes from the
 * editor goes through clean() so only a small, safe set of tags survives, and
 * the same HTML can be turned into Markdown or plain text for export/search.
 */
final class NoteHtml
{
    private const ALLOWED = [
        'p', 'br', 'h1', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del',
        'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'a', 'hr', 'div', 'span',
    ];

    private const DROP = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'head', 'title', 'template',
        'noscript', 'form', 'textarea', 'select', 'button', 'link', 'meta', 'img', 'video', 'audio',
    ];

    public static function clean(?string $html): string
    {
        $root = self::parse((string) $html);
        if (! $root) {
            return '';
        }
        self::sanitize($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $root->ownerDocument->saveHTML($child);
        }

        return trim($out);
    }

    public static function toMarkdown(?string $html): string
    {
        return self::convert((string) $html, true);
    }

    public static function toText(?string $html): string
    {
        return self::convert((string) $html, false);
    }

    private static function parse(string $html): ?DOMElement
    {
        if (trim($html) === '') {
            return null;
        }
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $doc->getElementsByTagName('div')->item(0);
    }

    private static function sanitize(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }
            if (! $child instanceof DOMElement) {
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->nodeName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);

                continue;
            }

            self::sanitize($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            self::cleanAttributes($child, $tag);
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $keep = [];
        if ($tag === 'a') {
            $href = preg_replace('/[\x00-\x20]+/', '', $el->getAttribute('href'));
            if (preg_match('#^(https?://|mailto:|tel:)#i', $href)) {
                $keep = ['href' => $href, 'target' => '_blank', 'rel' => 'noopener noreferrer'];
            }
        } elseif ($tag === 'ul' && $el->hasAttribute('data-checklist')) {
            $keep = ['data-checklist' => ''];
        } elseif ($tag === 'li' && $el->parentNode instanceof DOMElement && $el->parentNode->hasAttribute('data-checklist')) {
            $keep = ['data-checked' => $el->getAttribute('data-checked') === 'true' ? 'true' : 'false'];
        }

        foreach (iterator_to_array($el->attributes) as $attr) {
            $el->removeAttributeNode($attr);
        }
        foreach ($keep as $name => $value) {
            $el->setAttribute($name, $value);
        }
    }

    private static function convert(string $html, bool $md): string
    {
        $root = self::parse($html);
        if (! $root) {
            return '';
        }
        $out = self::render($root, $md, false);

        return trim(preg_replace("/\n{3,}/", "\n\n", $out));
    }

    private static function children(DOMNode $node, bool $md, bool $pre): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= self::render($child, $md, $pre);
        }

        return $out;
    }

    private static function render(DOMNode $node, bool $md, bool $pre): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return $pre ? $node->nodeValue : preg_replace('/\s+/', ' ', $node->nodeValue);
        }
        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->nodeName);
        $inner = fn () => self::children($node, $md, $pre);

        switch ($tag) {
            case 'br':
                return "\n";
            case 'hr':
                return $md ? "---\n\n" : "----------\n\n";
            case 'strong':
            case 'b':
                return $md ? '**'.$inner().'**' : $inner();
            case 'em':
            case 'i':
                return $md ? '_'.$inner().'_' : $inner();
            case 's':
            case 'strike':
            case 'del':
                return $md ? '~~'.$inner().'~~' : $inner();
            case 'code':
                return $md && ! $pre ? '`'.$inner().'`' : $inner();
            case 'a':
                $href = $node->getAttribute('href');
                $text = $inner();

                return $href === '' || $href === $text ? $text : ($md ? "[$text]($href)" : "$text ($href)");
            case 'pre':
                $text = rtrim(self::children($node, $md, true));

                return $md ? "```\n$text\n```\n\n" : "$text\n\n";
            case 'blockquote':
                $lines = explode("\n", trim($inner()));

                return implode("\n", array_map(fn ($l) => '> '.$l, $lines))."\n\n";
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
                $text = trim($inner());

                return ($md ? str_repeat('#', (int) $tag[1]).' ' : '').$text."\n\n";
            case 'ul':
            case 'ol':
                return self::renderList($node, $md)."\n";
            case 'p':
            case 'div':
                $text = trim($inner());

                return $text === '' ? '' : $text."\n\n";
            default:
                return $inner();
        }
    }

    private static function renderList(DOMElement $list, bool $md): string
    {
        $ordered = strtolower($list->nodeName) === 'ol';
        $checklist = $list->hasAttribute('data-checklist');
        $n = 0;
        $lines = [];

        foreach ($list->childNodes as $li) {
            if (! $li instanceof DOMElement || strtolower($li->nodeName) !== 'li') {
                continue;
            }
            $n++;
            $text = '';
            $nested = '';
            foreach ($li->childNodes as $child) {
                if ($child instanceof DOMElement && in_array(strtolower($child->nodeName), ['ul', 'ol'], true)) {
                    $sub = rtrim(self::renderList($child, $md));
                    $nested .= "\n".implode("\n", array_map(fn ($l) => '  '.$l, explode("\n", $sub)));
                } else {
                    $text .= self::render($child, $md, false);
                }
            }

            if ($checklist) {
                $box = $li->getAttribute('data-checked') === 'true' ? '[x] ' : '[ ] ';
                $bullet = ($md ? '- ' : '').$box;
            } elseif ($ordered) {
                $bullet = $n.'. ';
            } else {
                $bullet = $md ? '- ' : '• ';
            }
            $lines[] = $bullet.trim($text).$nested;
        }

        return implode("\n", $lines)."\n";
    }
}

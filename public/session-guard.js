// Sends the user back to the login page when the session has expired.
// Wraps fetch() so every background request (autosave, polling, TV displays) is covered.
(function () {
    var loginUrl = '/login';
    var redirecting = false;

    function toLogin() {
        if (redirecting) return;
        redirecting = true;
        location.assign(loginUrl);
    }

    var nativeFetch = window.fetch;
    window.fetch = function () {
        return nativeFetch.apply(this, arguments).then(function (res) {
            var sameOrigin = res.url && res.url.indexOf(location.origin) === 0;
            if (sameOrigin && (res.status === 401 || res.status === 419 || (res.redirected && new URL(res.url).pathname === loginUrl))) {
                toLogin();
            }
            return res;
        });
    };

    window.addEventListener('DOMContentLoaded', function () {
        if (!window.axios) return;
        window.axios.interceptors.response.use(null, function (err) {
            var s = err && err.response && err.response.status;
            if (s === 401 || s === 419) toLogin();
            return Promise.reject(err);
        });
    });
})();

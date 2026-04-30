window.DolphinPluginDemoHello = window.DolphinPluginDemoHello || {
    ready: true
};

window.DolphinPluginDemoHello.initShellNotifications = function () {
    document.querySelectorAll('[data-demo-notify-favorite]').forEach(function (item) {
        if (item.dataset.dpBound === '1') {
            return;
        }

        item.dataset.dpBound = '1';
        item.addEventListener('click', function (event) {
            event.preventDefault();
            item.classList.toggle('is-active');
        });
    });

    document.querySelectorAll('[data-demo-notify-close]').forEach(function (item) {
        if (item.dataset.dpCloseBound === '1') {
            return;
        }

        item.dataset.dpCloseBound = '1';
        item.addEventListener('click', function (event) {
            var dropdownRoot;
            var dropdownMenu;
            var dropdownToggle;

            event.preventDefault();
            dropdownRoot = item.closest('.dropdown');
            if (!dropdownRoot) {
                return;
            }

            dropdownMenu = dropdownRoot.querySelector('.dropdown-menu');
            dropdownToggle = dropdownRoot.querySelector('[data-bs-toggle="dropdown"]');

            if (!dropdownToggle) {
                return;
            }

            if (window.bootstrap && window.bootstrap.Dropdown) {
                window.bootstrap.Dropdown.getOrCreateInstance(dropdownToggle).hide();
                return;
            }

            dropdownToggle.click();

            if (dropdownMenu) {
                dropdownMenu.classList.remove('show');
            }
            dropdownRoot.classList.remove('show');
            dropdownToggle.setAttribute('aria-expanded', 'false');
        });
    });
};

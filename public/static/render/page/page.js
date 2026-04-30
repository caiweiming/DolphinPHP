(function () {
    'use strict';

    function getContentTabs(root) {
        return root.querySelectorAll('a[data-dp-tab-mode="content"][data-bs-toggle="tab"]');
    }

    function restoreTab(tabs, savedKey) {
        if (!savedKey) {
            return;
        }

        for (let i = 0; i < tabs.length; i++) {
            const tab = tabs[i];
            if (tab.getAttribute('data-dp-tab-key') !== savedKey || tab.classList.contains('disabled')) {
                continue;
            }

            if (window.bootstrap && window.bootstrap.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(tab).show();
            } else {
                tab.click();
            }
            break;
        }
    }

    function bindRemember(root) {
        if (!root || root.dataset.dpTabsRememberBound === '1') {
            return;
        }

        const remember = root.getAttribute('data-dp-tabs-remember') === '1';
        const rootId = root.getAttribute('id') || '';
        if (!remember || rootId === '' || !window.localStorage) {
            return;
        }

        const tabs = getContentTabs(root);
        if (!tabs.length) {
            return;
        }

        const storageKey = 'dp:page:tabs:' + rootId;
        restoreTab(tabs, localStorage.getItem(storageKey));

        for (let i = 0; i < tabs.length; i++) {
            tabs[i].addEventListener('click', function (event) {
                const key = event.currentTarget.getAttribute('data-dp-tab-key') || '';
                if (key !== '') {
                    localStorage.setItem(storageKey, key);
                }
            });

            tabs[i].addEventListener('shown.bs.tab', function (event) {
                const key = event.target.getAttribute('data-dp-tab-key') || '';
                if (key !== '') {
                    localStorage.setItem(storageKey, key);
                }
            });
        }

        root.dataset.dpTabsRememberBound = '1';
    }

    function initPageTabs() {
        const tabsRoots = document.querySelectorAll('[data-dp-page-tabs="1"]');
        for (let i = 0; i < tabsRoots.length; i++) {
            bindRemember(tabsRoots[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPageTabs);
    } else {
        initPageTabs();
    }
})();

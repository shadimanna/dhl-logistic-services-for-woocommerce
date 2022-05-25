let args = {
    "wz_nav_style": "dots", // dots, tabs, progress
    "wz_ori" : "horizontal",
    "buttons": true,
    "navigation": 'all', // buttons, nav, all
    "next": '<span class="dashicons dashicons-arrow-right-alt2"></span>',
    "prev": '<span class="dashicons dashicons-arrow-left-alt2"></span>',
};

const wizard = new Wizard(args);

wizard.init();
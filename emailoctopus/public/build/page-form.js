!function(e,t){const o=e(document.getElementById("emailoctopus-form-automatic-display")),a=e(document.getElementById("emailoctopus-form-post-types-container"));o.on("change",(function(){"none"!==o.val()?a.show():a.hide()})),o.trigger("change"),e("li#toplevel_page_emailoctopus-forms").removeClass("wp-not-current-submenu").addClass("wp-has-current-submenu wp-menu-open"),e("li#toplevel_page_emailoctopus-forms .wp-first-item").addClass("current"),e("li#toplevel_page_emailoctopus-forms .wp-first-item a").addClass("current").attr("aria-current","page")}(window.jQuery,window.emailOctopusL10n);
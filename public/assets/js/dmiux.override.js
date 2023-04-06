function autoClose(event) {
    event.stopPropagation();
    var clicked_element = event.target;
    if (clicked_element.closest(".dmiux_popup__window") || clicked_element.closest(".notyf") || clicked_element.closest("#loading")) {
        return;
    }
    if(typeof(keepOpen) != "undefined") {
        if(keepOpen(event)) {
            return;
        }
    }
    if ($('.dmiux_popup').hasClass('dmiux_popup_visible')) {
        closeModal();
    }
}
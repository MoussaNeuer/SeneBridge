/* SeneBridge — back-office : bouton « Tout cocher » des cases de permissions. */
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('toggleAll');
    if (!toggle) {
        return;
    }
    toggle.addEventListener('click', function () {
        var boxes = document.querySelectorAll('input[name="permissions[]"]');
        var anyUnchecked = Array.prototype.some.call(boxes, function (b) { return !b.checked; });
        boxes.forEach(function (b) { b.checked = anyUnchecked; });
    });
});
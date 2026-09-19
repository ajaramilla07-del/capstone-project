document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.qty-box button').forEach(function (button) {
        button.addEventListener('click', function () {
            const qtyValue = button.parentElement.querySelector('.qty-value');
            const current = Number(qtyValue.textContent.trim());
            let next = current;

            if (button.textContent.trim() === '+') {
                next = current + 1;
            } else if (current > 1) {
                next = current - 1;
            }

            qtyValue.textContent = next;
        });
    });

    document.querySelectorAll('.tab-btn').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(function (item) {
                item.classList.remove('active');
            });
            tab.classList.add('active');
        });
    });
});

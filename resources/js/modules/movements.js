export function initMovements() {
    document.querySelectorAll('[data-movement]').forEach(button => {
        button.addEventListener('click', () => {
            const direction = button.dataset.movement;
            document.querySelectorAll('[data-movement]').forEach(item => item.classList.toggle('active', item === button));
            document.querySelectorAll('[data-movement-list]').forEach(list => {
                list.hidden = list.dataset.movementList !== direction;
            });
        });
    });
}

export function initPrediction() {
    document.querySelectorAll('#horizonButtons button').forEach(button => button.addEventListener('click', () => {
        document.querySelectorAll('#horizonButtons button').forEach(item => item.classList.remove('active'));
        button.classList.add('active');
        document.querySelector('#predictionValue').textContent = button.dataset.value;
    }));
}

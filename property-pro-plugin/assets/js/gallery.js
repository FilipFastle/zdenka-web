/* Property Pro - Gallery & Filter */
document.addEventListener('DOMContentLoaded', function() {
    var filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var filter = this.dataset.filter;
            filterBtns.forEach(function(b) {
                b.style.background = '#fff';
                b.style.color = '#2c3e50';
            });
            this.style.background = '#2c3e50';
            this.style.color = '#fff';
        });
    });
});

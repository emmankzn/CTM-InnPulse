// Fixed event handlers extracted from the page templates. No eval or dynamic code compilation.
const handlers = {
    h0: function(event) { toggleSidebar() },
    h1: function(event) { signOut() },
    h2: function(event) { openInviteModal() },
    h3: function(event) { filterUsers() },
    h4: function(event) { closeUserModal() },
    h5: function(event) { saveUser(event) },
    h6: function(event) { toggleSidebar() },
    h7: function(event) { signOut() },
    h8: function(event) { loadLogs() },
    h9: function(event) { setFilter(this,'ALL') },
    h10: function(event) { setFilter(this,'INFO') },
    h11: function(event) { setFilter(this,'WARN') },
    h12: function(event) { setFilter(this,'CRITICAL') },
    h13: function(event) { filterLogs() },
    h14: function(event) { searchTable() },
    h15: function(event) { signOut() },
    h16: function(event) { openNewReservationModal() },
    h17: function(event) { closeResModal() },
    h18: function(event) { saveReservation(event) },
    h19: function(event) { closeReceiptModal() },
    h20: function(event) { openLoginModal() },
    h21: function(event) { toggleMobileMenu() },
    h22: function(event) { closeMobileMenu() },
    h23: function(event) { closeMobileMenu() },
    h24: function(event) { closeMobileMenu() },
    h25: function(event) { closeMobileMenu() },
    h26: function(event) { closeMobileMenu() },
    h27: function(event) { closeMobileMenu();openLoginModal(); },
    h28: function(event) { filterRooms() },
    h29: function(event) { submitReview(event) },
    h30: function(event) { setRating(1) },
    h31: function(event) { setRating(2) },
    h32: function(event) { setRating(3) },
    h33: function(event) { setRating(4) },
    h34: function(event) { setRating(5) },
    h35: function(event) { submitContact(event) },
    h36: function(event) { closeReserveModal() },
    h37: function(event) { submitReservation(event) },
    h38: function(event) { closeReserveModal() },
    h39: function(event) { this.removeAttribute('data-evt-error'); this.src='assets/placeholder.svg' },
    h40: function(event) { handleLoginOverlayClick(event) },
    h41: function(event) { closeLoginModal() },
    h42: function(event) { handleLoginModal(event) },
    h43: function(event) { toggleLmPw() },
    h44: function(event) { loadQuery('bookings') },
    h45: function(event) { loadQuery('failed') },
    h46: function(event) { loadQuery('schema') },
    h47: function(event) { loadQuery('locked') },
    h48: function(event) { loadQuery('revenue') },
    h49: function(event) { toggleSidebar() },
    h50: function(event) { signOut() },
    h51: function(event) { saveSection('hotel') },
    h52: function(event) { saveSection('reservation') },
    h53: function(event) { saveSection('toggles') },
    h54: function(event) { clearAuditLogs() },
};
const calls = {
    deleteReservation: (...args) => window.deleteReservation(...args),
    openEditModal: (...args) => window.openEditModal(...args),
    openEditUserModal: (...args) => window.openEditUserModal(...args),
    openReceiptModal: (...args) => window.openReceiptModal(...args),
    openReserveModal: (...args) => window.openReserveModal(...args),
    removeUser: (...args) => window.removeUser(...args),
    resetPassword: (...args) => window.resetPassword(...args),
    toggleExpand: (...args) => window.toggleExpand(...args),
    toggleLock: (...args) => window.toggleLock(...args),
};
for (const type of ['click', 'submit', 'input', 'change', 'error']) {
    document.addEventListener(type, event => {
        const element = event.target.closest?.(`[data-evt-${type}]`);
        if (element && !element.disabled) {
            if (type === 'submit') event.preventDefault();
            handlers[element.getAttribute(`data-evt-${type}`)]?.call(element, event);
        }
        if (type !== 'click') return;
        const target = event.target.closest?.('[data-call]');
        if (!target || target.disabled) return;
        const action = calls[target.dataset.call];
        if (action) action(...JSON.parse(target.dataset.args || '[]'));
    }, type === 'error');
}

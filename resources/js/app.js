import './bootstrap';
import './notification-bell';
import './theme';

document.addEventListener('keydown', (event) => {
    const target = event.target;
    const isTyping = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable);

    if (event.key === '/' && !isTyping && !event.ctrlKey && !event.metaKey && !event.altKey) {
        const search = document.getElementById('global-search');
        if (search) {
            event.preventDefault();
            search.focus();
        }
    }
});

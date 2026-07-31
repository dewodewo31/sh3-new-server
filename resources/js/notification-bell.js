document.addEventListener('alpine:init', () => {
    window.Alpine.data('notificationBell', (userId) => ({
        open: false,
        unreadCount: 0,
        notifications: [],

        async init() {
            await this.fetchNotifications();
            this.listen();
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.fetchNotifications();
            }
        },

        iconMeta(icon) {
            const icons = {
                bell:       { cls: 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400', d: 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0' },
                money:      { cls: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400', d: 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z' },
                check:      { cls: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400', d: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
                x:          { cls: 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400', d: 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
                'user-plus':{ cls: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400', d: 'M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z' },
                megaphone:  { cls: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400', d: 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46' },
                cart:       { cls: 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400', d: 'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z' },
                'badge-check': { cls: 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400', d: 'M9 12.75L11.25 15 15 9.75m-3-7.035A3.015 3.015 0 0012 2.25a3.015 3.015 0 00-2.94 2.465c-.29.028-.573.074-.847.135a3.01 3.01 0 00-2.028.985 3.01 3.01 0 00-.555 2.225c-.206.227-.393.469-.558.72a3.015 3.015 0 00-.512 2.41 3.01 3.01 0 001.34 1.86c-.041.194-.07.394-.07.605 0 .21.029.41.07.605a3.012 3.012 0 00-1.34 1.86 3.01 3.01 0 00.512 2.41c.165.251.352.493.558.72a3.01 3.01 0 00.555 2.225c.498.578 1.213.93 2.028.985.274.06.557.106.847.135A3.015 3.015 0 0012 21.75a3.016 3.016 0 002.94-2.465 9.9 9.9 0 00.847-.135 3.01 3.01 0 002.028-.985 3.01 3.01 0 00.555-2.225c.206-.227.393-.469.558-.72a3.012 3.012 0 00.512-2.41 3.011 3.011 0 00-1.34-1.86c.041-.194.07-.394.07-.605 0-.21-.029-.41-.07-.605a3.01 3.01 0 001.34-1.86 3.012 3.012 0 00-.512-2.41 3.01 3.01 0 00-.558-.72 3.01 3.01 0 00-.555-2.225 3.01 3.01 0 00-2.028-.985 9.9 9.9 0 00-.847-.135A3.015 3.015 0 0012 2.25zm-3 9.75l2.25 2.25 3.75-4.5' },
                calendar:   { cls: 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400', d: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5' },
            };

            return icons[icon] || icons.bell;
        },

        async fetchNotifications() {
            try {
                const response = await window.axios.get('/admin/notifications');
                this.notifications = response.data.data;
                this.unreadCount = response.data.unread_count;
            } catch (error) {
                console.error('Gagal memuat notifikasi:', error);
            }
        },

        listen() {
            if (!window.Echo || !userId) {
                return;
            }

            window.Echo.private(`App.Models.User.${userId}`)
                .notification((notification) => {
                    this.notifications.unshift({
                        id: `live-${Date.now()}`,
                        title: notification.title,
                        body: notification.body,
                        icon: notification.icon,
                        url: notification.url,
                        read_at: null,
                        created_at: 'Baru saja',
                    });
                    this.notifications = this.notifications.slice(0, 20);
                    this.unreadCount++;
                });
        },

        async markAsRead(notification) {
            if (notification.read_at || notification.id.startsWith('live-')) {
                return;
            }

            notification.read_at = true;

            try {
                await window.axios.post(`/admin/notifications/${notification.id}/read`);
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            } catch (error) {
                console.error('Gagal menandai notifikasi:', error);
            }
        },

        async markAllAsRead() {
            try {
                await window.axios.post('/admin/notifications/read-all');
                this.notifications.forEach((notification) => {
                    notification.read_at = true;
                });
                this.unreadCount = 0;
            } catch (error) {
                console.error('Gagal menandai semua notifikasi:', error);
            }
        },
    }));
});

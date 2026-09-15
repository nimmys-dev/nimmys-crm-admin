@php
    $user = auth()->user();
    $name = $user?->name ?? 'Guest';
    $email = $user?->email;
@endphp

<header class="pc-header">
    <div class="header-wrapper flex max-sm:px-[15px] px-[25px] grow">

        <div class="me-auto pc-mob-drp">
            <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">

                <li class="pc-h-item pc-sidebar-collapse max-lg:hidden lg:inline-flex">
                    <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="sidebar-hide" aria-label="Toggle sidebar">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>

                <li class="pc-h-item pc-sidebar-popup lg:hidden">
                    <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="mobile-collapse" aria-label="Open menu">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>

                <!-- <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
                        aria-haspopup="true" aria-expanded="false" aria-label="Search">
                        <i class="ti ti-search"></i>
                    </a>
                    <div class="dropdown-menu pc-h-dropdown drp-search">
                        <form class="px-2 py-1" action="{{ route('search') }}" method="GET" role="search">
                            <input type="search" name="q" value="{{ request('q') }}"
                                class="form-control !border-0 !shadow-none" placeholder="Search…" aria-label="Search" />
                        </form>
                    </div>
                </li> -->

            </ul>
        </div>

        <div class="ms-auto">
            <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">

                {{-- Theme --}}
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
                        aria-haspopup="true" aria-expanded="false" aria-label="Change theme">
                        <i class="ti ti-sun"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <a href="#!" class="dropdown-item" onclick="layout_change('light')">
                            <i class="ti ti-sun"></i><span>Light</span>
                        </a>
                        <a href="#!" class="dropdown-item" onclick="layout_change('dark')">
                            <i class="ti ti-moon"></i><span>Dark</span>
                        </a>
                    </div>
                </li>

                {{-- Notifications --}}
                <!-- <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
                        aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                        <i class="ti ti-bell"></i>
                    </a>
                    <div class="dropdown-menu dropdown-notification dropdown-menu-end pc-h-dropdown p-2">
                        <div class="dropdown-header flex items-center justify-between py-4 px-5">
                            <h5 class="m-0">Notifications</h5>
                        </div>
                        <div class="dropdown-body header-notification-scroll relative py-4 px-5"
                            style="max-height: calc(100vh - 215px)">
                            <div class="text-center py-8">
                                <i class="ti ti-bell-off text-[32px] text-muted"></i>
                                <p class="mb-0 mt-3 text-muted">You're all caught up.</p>
                            </div>
                        </div>
                    </div>
                </li> -->


<li class="dropdown pc-h-item">
    <a class="pc-head-link dropdown-toggle me-0 position-relative"
       data-pc-toggle="dropdown"
       href="#"
       role="button"
       aria-haspopup="true"
       aria-expanded="false"
       aria-label="Notifications">

        <i class="ti ti-bell"></i>

        <!-- Notification Count -->
        <span id="notificationCount"
              class="badge bg-danger rounded-pill"
              style="
                    display:none;
                    position:absolute;
                    top:2px;
                    right:0;
                    min-width:18px;
                    height:18px;
                    line-height:18px;
                    padding:0 5px;
                    font-size:10px;
              ">
            0
        </span>
    </a>

    <div class="dropdown-menu dropdown-notification dropdown-menu-end pc-h-dropdown p-2"
         style="width:360px;">

        <!-- Header -->
        <div class="dropdown-header flex items-center justify-between py-4 px-5">

            <h5 class="m-0">
                Notifications
            </h5>

            <button type="button"
                    id="clearNotifications"
                    class="btn btn-sm btn-link text-danger p-0">
                Clear all
            </button>

        </div>

        <!-- Notification Body -->
        <div id="notificationList"
             class="dropdown-body header-notification-scroll relative py-2 px-3"
             style="max-height:calc(100vh - 215px); overflow-y:auto;">

            <!-- Notifications will be loaded here -->

        </div>

    </div>
</li>



                {{-- Profile --}}
                <li class="dropdown pc-h-item header-user-profile">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-pc-toggle="dropdown" href="#"
                        role="button" aria-haspopup="true" data-pc-auto-close="outside" aria-expanded="false"
                        aria-label="Account menu">
                        <i class="ti ti-user"></i>
                    </a>
                    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown p-2 overflow-hidden">

                        <div class="dropdown-header flex items-center justify-between py-4 px-5 bg-primary-500">
                            <div class="flex mb-1 items-center">
                                <div class="shrink-0">
                                    <span class="w-10 h-10 rounded-full bg-white/20 text-white flex items-center justify-center font-medium">
                                        {{ Str::of($name)->substr(0, 1)->upper() }}
                                    </span>
                                </div>
                                <div class="grow ms-3">
                                    <h6 class="mb-1 text-white">{{ $name }}</h6>
                                    @if ($email)
                                        <span class="text-white">{{ $email }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="dropdown-body py-4 px-5">
                            <a href="{{ route('profile.index') }}" class="dropdown-item">
                                <span><i class="ti ti-user me-2"></i><span>Profile</span></span>
                            </a>
                            <a href="{{ route('settings.index') }}" class="dropdown-item">
                                <span><i class="ti ti-settings me-2"></i><span>Settings</span></span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}" class="grid my-3">
                                @csrf
                                <button type="submit" class="btn btn-primary flex items-center justify-center">
                                    <i class="ti ti-logout me-2"></i>Log out
                                </button>
                            </form>
                        </div>

                    </div>
                </li>

            </ul>
        </div>

    </div>
</header>


<script>

    /*
    |--------------------------------------------------------------------------
    | Get Notifications
    |--------------------------------------------------------------------------
    */

    function getNotifications() {

        return JSON.parse(
            localStorage.getItem('crm_notifications') || '[]'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Save Notifications
    |--------------------------------------------------------------------------
    */

    function saveNotifications(notifications) {

        localStorage.setItem(
            'crm_notifications',
            JSON.stringify(notifications)
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Display Notifications
    |--------------------------------------------------------------------------
    */

    function displayNotifications() {

        const notifications = getNotifications();

        const list = document.getElementById('notificationList');
        const count = document.getElementById('notificationCount');

        if (!list || !count) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Unread Count
        |--------------------------------------------------------------------------
        */

        const unreadCount = notifications.filter(
            notification => !notification.read
        ).length;


        /*
        |--------------------------------------------------------------------------
        | Show / Hide Badge
        |--------------------------------------------------------------------------
        */

        if (unreadCount > 0) {

            count.innerText = unreadCount > 99
                ? '99+'
                : unreadCount;

            count.style.display = 'inline-block';

        } else {

            count.style.display = 'none';

        }


        /*
        |--------------------------------------------------------------------------
        | No Notifications
        |--------------------------------------------------------------------------
        */

        if (notifications.length === 0) {

            list.innerHTML = `
                <div class="text-center py-8">

                    <i class="ti ti-bell-off text-[32px] text-muted"></i>

                    <p class="mb-0 mt-3 text-muted">
                        You're all caught up.
                    </p>

                </div>
            `;

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Notification List
        |--------------------------------------------------------------------------
        */

        list.innerHTML = notifications.map((notification, index) => {

            return `
                <div
                    class="notification-item p-3 mb-2 rounded border
                           ${notification.read ? '' : 'bg-light'}"
                    data-index="${index}"
                    style="cursor:pointer;"
                >

                    <div class="flex items-start gap-3">

                        <!-- Icon -->
                        <div class="flex-shrink-0">

                            <span
                                class="d-flex align-items-center justify-content-center
                                       rounded-circle bg-primary text-white"
                                style="width:36px;height:36px;"
                            >
                                <i class="ti ti-bell"></i>
                            </span>

                        </div>


                        <!-- Content -->
                        <div class="flex-grow-1">

                            <div class="flex items-center justify-between">

                                <strong class="text-dark">
                                    ${escapeHtml(notification.title || 'Notification')}
                                </strong>

                                ${
                                    !notification.read
                                    ? `
                                        <span
                                            class="badge bg-primary rounded-pill"
                                            style="font-size:9px;"
                                        >
                                            New
                                        </span>
                                    `
                                    : ''
                                }

                            </div>


                            <p class="mb-1 mt-1 text-muted"
                               style="font-size:13px;">
                                ${escapeHtml(notification.body || '')}
                            </p>


                            <small class="text-muted">
                                ${formatNotificationTime(notification.created_at)}
                            </small>

                        </div>

                    </div>

                </div>
            `;

        }).join('');


        /*
        |--------------------------------------------------------------------------
        | Click Notification
        |--------------------------------------------------------------------------
        */

        document.querySelectorAll('.notification-item')
            .forEach(item => {

                item.addEventListener('click', function () {

                    const index = parseInt(
                        this.getAttribute('data-index')
                    );

                    markNotificationAsRead(index);

                });

            });

    }


    /*
    |--------------------------------------------------------------------------
    | Mark Notification As Read
    |--------------------------------------------------------------------------
    */

    function markNotificationAsRead(index) {

        const notifications = getNotifications();

        if (notifications[index]) {

            notifications[index].read = true;

            saveNotifications(notifications);

            displayNotifications();

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Add Firebase Notification
    |--------------------------------------------------------------------------
    */

    function addFirebaseNotification(title, body, data = {}) {

        const notifications = getNotifications();


        notifications.unshift({

            id: Date.now(),

            title: title || 'Nimmys CRM',

            body: body || '',

            data: data,

            read: false,

            created_at: new Date().toISOString()

        });


        /*
        |--------------------------------------------------------------------------
        | Keep Latest 50 Notifications
        |--------------------------------------------------------------------------
        */

        const latestNotifications = notifications.slice(0, 50);


        saveNotifications(latestNotifications);

        displayNotifications();

    }


    /*
    |--------------------------------------------------------------------------
    | Clear All Notifications
    |--------------------------------------------------------------------------
    */

    document.addEventListener('click', function (event) {

        if (event.target.closest('#clearNotifications')) {

            localStorage.removeItem('crm_notifications');

            displayNotifications();

        }

    });


    /*
    |--------------------------------------------------------------------------
    | HTML Escape
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {

        const div = document.createElement('div');

        div.textContent = value ?? '';

        return div.innerHTML;

    }


    /*
    |--------------------------------------------------------------------------
    | Notification Time
    |--------------------------------------------------------------------------
    */

    function formatNotificationTime(date) {

        if (!date) {
            return '';
        }

        const notificationDate = new Date(date);

        const now = new Date();

        const difference =
            Math.floor(
                (now - notificationDate) / 1000
            );


        if (difference < 60) {

            return 'Just now';

        }


        if (difference < 3600) {

            const minutes = Math.floor(
                difference / 60
            );

            return minutes + ' min ago';

        }


        if (difference < 86400) {

            const hours = Math.floor(
                difference / 3600
            );

            return hours + ' hour ago';

        }


        if (difference < 172800) {

            return 'Yesterday';

        }


        return notificationDate.toLocaleDateString();

    }


    /*
    |--------------------------------------------------------------------------
    | Load Notifications On Page Load
    |--------------------------------------------------------------------------
    */

    document.addEventListener('DOMContentLoaded', function () {

        displayNotifications();

    });

</script>



<template>
  <div class="dashboard-layout">
    <AppSidebar
      :mobile-open="mobileSidebarOpen"
      :collapsed="desktopSidebarCollapsed"
      @close="closeMobileSidebar"
      @toggle-collapse="toggleDesktopSidebar"
      @user-menu="openUserDropdown"
    />

    <div
      class="main-wrapper"
      :class="{
        'main-wrapper-collapsed':
          desktopSidebarCollapsed,
      }"
    >
      <!-- =========================
           Topbar
      ========================== -->
      <header class="app-topbar">
        <div class="topbar-left">
          <!-- Mobile sidebar opener -->
          <button
            type="button"
            class="topbar-icon-button d-lg-none"
            aria-label="Open sidebar"
            @click="openMobileSidebar"
          >
            <i class="bi bi-list"></i>
          </button>

          <!-- Desktop sidebar toggle -->
          <button
            type="button"
            class="topbar-icon-button d-none d-lg-flex"
            :title="
              desktopSidebarCollapsed
                ? 'Expand sidebar'
                : 'Collapse sidebar'
            "
            :aria-label="
              desktopSidebarCollapsed
                ? 'Expand sidebar'
                : 'Collapse sidebar'
            "
            @click="toggleDesktopSidebar"
          >
            <i
              class="bi"
              :class="
                desktopSidebarCollapsed
                  ? 'bi-layout-sidebar-inset'
                  : 'bi-layout-sidebar-inset-reverse'
              "
            ></i>
          </button>

          <div class="page-heading">
            <h2 class="page-heading-title">
              {{ currentPageTitle }}
            </h2>

            <p class="page-heading-subtitle">
              Restaurant Management System
            </p>
          </div>
        </div>

        <div class="topbar-right">
          <!-- Notification -->
          <button
            type="button"
            class="topbar-icon-button notification-button"
            title="Notifications"
            aria-label="Notifications"
          >
            <i class="bi bi-bell"></i>

            <span class="notification-indicator"></span>
          </button>

          <!-- User dropdown -->
          <div
            ref="userDropdownContainer"
            class="dropdown user-dropdown-container"
            @click.stop
          >
            <button
              ref="userDropdownButton"
              class="user-dropdown-button dropdown-toggle"
              type="button"
              :class="{ show: userDropdownOpen }"
              :aria-expanded="userDropdownOpen"
              @click.stop="toggleUserDropdown"
            >
              <div class="topbar-avatar">
                {{ userInitials }}
              </div>

              <div class="user-dropdown-details d-none d-sm-block">
                <div class="topbar-user-name">
                  {{ authStore.user?.name || 'User' }}
                </div>

                <div class="topbar-user-role">
                  {{ roleDisplayName }}
                </div>
              </div>
            </button>

            <ul
              v-show="userDropdownOpen"
              class="dropdown-menu dropdown-menu-end user-dropdown-menu show"
              @click.stop
            >
              <li class="dropdown-user-summary">
                <div class="dropdown-avatar">
                  {{ userInitials }}
                </div>

                <div>
                  <div class="fw-semibold">
                    {{ authStore.user?.name || 'User' }}
                  </div>

                  <div class="small text-secondary">
                    {{ roleDisplayName }}
                  </div>

                  <div class="small text-secondary">
                    {{ authStore.user?.email }}
                  </div>
                </div>
              </li>

              <li>
                <hr class="dropdown-divider" />
              </li>

              <li>
                <RouterLink
                  to="/settings"
                  class="dropdown-item"
                  @click="closeUserDropdown"
                >
                  <i class="bi bi-gear me-2"></i>
                  Account Settings
                </RouterLink>
              </li>

              <li>
                <button
                  type="button"
                  class="dropdown-item text-danger"
                  :disabled="authStore.loading"
                  @click="handleLogout"
                >
                  <i class="bi bi-box-arrow-right me-2"></i>

                  {{
                    authStore.loading
                      ? 'Signing out...'
                      : 'Logout'
                  }}
                </button>
              </li>
            </ul>
          </div>
        </div>
      </header>

      <!-- =========================
           Main Content
      ========================== -->
      <main class="dashboard-content">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
} from 'vue'

import {
  RouterLink,
  RouterView,
  useRoute,
  useRouter,
} from 'vue-router'


import AppSidebar from '@/components/AppSidebar.vue'
import { useAuthStore } from '@/stores/auth'

const SIDEBAR_STORAGE_KEY =
  'rms_sidebar_collapsed'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const mobileSidebarOpen = ref(false)

const desktopSidebarCollapsed = ref(
  localStorage.getItem(
    SIDEBAR_STORAGE_KEY,
  ) === 'true',
)

const userDropdownContainer = ref(null)
const userDropdownOpen = ref(false)

/*
|--------------------------------------------------------------------------
| Computed values
|--------------------------------------------------------------------------
*/

const currentPageTitle = computed(() => {
  return route.meta.title || 'Dashboard'
})

const roleDisplayName = computed(() => {
  return (
    authStore.user?.role?.display_name ||
    authStore.user?.role?.name ||
    'User'
  )
})

const userInitials = computed(() => {
  const name = authStore.user?.name?.trim()

  if (!name) {
    return 'U'
  }

  const words = name
    .split(/\s+/)
    .filter(Boolean)

  if (words.length === 1) {
    return words[0]
      .charAt(0)
      .toUpperCase()
  }

  return `${words[0].charAt(0)}${words[
    words.length - 1
  ].charAt(0)}`.toUpperCase()
})

/*
|--------------------------------------------------------------------------
| Sidebar methods
|--------------------------------------------------------------------------
*/

function toggleDesktopSidebar() {
  desktopSidebarCollapsed.value =
    !desktopSidebarCollapsed.value

  localStorage.setItem(
    SIDEBAR_STORAGE_KEY,
    String(
      desktopSidebarCollapsed.value,
    ),
  )
}

function openMobileSidebar() {
  mobileSidebarOpen.value = true
  document.body.classList.add(
    'sidebar-mobile-active',
  )
}

function closeMobileSidebar() {
  mobileSidebarOpen.value = false
  document.body.classList.remove(
    'sidebar-mobile-active',
  )
}

/*
|--------------------------------------------------------------------------
| User dropdown
|--------------------------------------------------------------------------
*/

function toggleUserDropdown() {
  userDropdownOpen.value =
    !userDropdownOpen.value
}

function openUserDropdown() {
  userDropdownOpen.value = true
}

function closeUserDropdown() {
  userDropdownOpen.value = false
}

function handleDocumentClick(event) {
  const container =
    userDropdownContainer.value

  if (
    container &&
    !container.contains(event.target)
  ) {
    closeUserDropdown()
  }
}

function handleEscapeKey(event) {
  if (event.key === 'Escape') {
    closeUserDropdown()
  }
}

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

async function handleLogout() {
  closeUserDropdown()
  closeMobileSidebar()

  try {
    await authStore.logout()
  } finally {
    await router.replace('/login')
  }
}

/*
|--------------------------------------------------------------------------
| Responsive resize handling
|--------------------------------------------------------------------------
*/

function handleWindowResize() {
  if (window.innerWidth >= 992) {
    closeMobileSidebar()
  }
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
  window.addEventListener(
    'resize',
    handleWindowResize,
  )

  document.addEventListener(
    'click',
    handleDocumentClick,
  )

  document.addEventListener(
    'keydown',
    handleEscapeKey,
  )
})

onBeforeUnmount(() => {
  window.removeEventListener(
    'resize',
    handleWindowResize,
  )

  document.removeEventListener(
    'click',
    handleDocumentClick,
  )

  document.removeEventListener(
    'keydown',
    handleEscapeKey,
  )

  document.body.classList.remove(
    'sidebar-mobile-active',
  )
})
</script>

<style scoped>
/* =========================================================
   Layout
========================================================= */

.dashboard-layout {
  min-height: 100vh;
}

.main-wrapper {
  min-height: 100vh;

  margin-left: 270px;

  background: #f5f7fb;

  transition:
    margin-left 0.25s ease;
}

.main-wrapper-collapsed {
  margin-left: 82px;
}

/* =========================================================
   Topbar
========================================================= */

.app-topbar {
  position: sticky;
  z-index: 1000;
  top: 0;

  display: flex;
  min-height: 72px;
  align-items: center;
  justify-content: space-between;

  border-bottom:
    1px solid
    #e7eaf0;

  background:
    rgba(255, 255, 255, 0.96);

  padding:
    0
    1.5rem;

  backdrop-filter: blur(12px);

  box-shadow:
    0 0.15rem 0.75rem
    rgba(15, 23, 42, 0.035);
}

.topbar-left,
.topbar-right {
  display: flex;
  align-items: center;
}

.topbar-left {
  min-width: 0;
  gap: 0.85rem;
}

.topbar-right {
  gap: 0.65rem;
}

.topbar-icon-button {
  position: relative;

  display: flex;
  width: 42px;
  height: 42px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;

  border:
    1px solid
    #e8ebf0;

  border-radius: 0.75rem;

  background: #ffffff;
  color: #495057;

  font-size: 1.08rem;

  transition:
    border-color 0.2s ease,
    background 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease;
}

.topbar-icon-button:hover {
  transform: translateY(-1px);

  border-color: #dbe4ff;

  background: #eef3ff;
  color: #0d6efd;
}

.notification-indicator {
  position: absolute;
  top: 9px;
  right: 9px;

  width: 7px;
  height: 7px;

  border:
    2px solid
    #ffffff;

  border-radius: 50%;

  background: #dc3545;
}

/* =========================================================
   Page heading
========================================================= */

.page-heading {
  min-width: 0;
}

.page-heading-title {
  margin: 0;

  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  color: #1f2937;
  font-size: 0.98rem;
  font-weight: 700;
}

.page-heading-subtitle {
  margin: 0.1rem 0 0;

  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  color: #8a94a6;
  font-size: 0.72rem;
}

/* =========================================================
   User dropdown
========================================================= */

.user-dropdown-container {
  position: relative;
}


.user-dropdown-button {
  display: flex;
  min-height: 46px;
  align-items: center;
  gap: 0.7rem;

  border:
    1px solid
    #e8ebf0;

  border-radius: 0.85rem;

  background: #ffffff;

  padding:
    0.3rem
    0.75rem
    0.3rem
    0.35rem;

  color: #343a40;

  text-align: left;

  transition:
    border-color 0.2s ease,
    background 0.2s ease,
    box-shadow 0.2s ease;
}

.user-dropdown-button:hover,
.user-dropdown-button.show {
  border-color: #dbe4ff;

  background: #f8faff;

  box-shadow:
    0 0.25rem 0.8rem
    rgba(13, 110, 253, 0.06);
}

.topbar-avatar,
.dropdown-avatar {
  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background:
    linear-gradient(
      135deg,
      #0d6efd,
      #4f46e5
    );

  color: #ffffff;
  font-weight: 700;
}

.topbar-avatar {
  width: 36px;
  height: 36px;

  font-size: 0.7rem;
}

.user-dropdown-details {
  min-width: 0;
  max-width: 150px;
}

.topbar-user-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  font-size: 0.78rem;
  font-weight: 650;
}

.topbar-user-role {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  color: #8a94a6;
  font-size: 0.67rem;
}

.user-dropdown-menu {
  position: absolute;
  z-index: 1200;
  top: 100%;
  right: 0;
  left: auto;

  display: block;
  width: 270px;

  margin-top: 0.65rem !important;

  border:
    1px solid
    #e8ebf0;

  border-radius: 0.9rem;

  box-shadow:
    0 0.75rem 2rem
    rgba(15, 23, 42, 0.12);

  padding: 0.5rem;
}

.dropdown-user-summary {
  display: flex;
  align-items: center;
  gap: 0.75rem;

  padding: 0.65rem;
}

.dropdown-avatar {
  width: 44px;
  height: 44px;
  flex-shrink: 0;

  font-size: 0.75rem;
}

.dropdown-item {
  border-radius: 0.55rem;

  padding:
    0.6rem
    0.75rem;
}

/* =========================================================
   Content
========================================================= */

.dashboard-content {
  min-height:
    calc(100vh - 72px);

  padding: 1.5rem;
}

/* =========================================================
   Responsive
========================================================= */

@media (max-width: 991.98px) {
  .main-wrapper,
  .main-wrapper-collapsed {
    margin-left: 0;
  }
}

@media (max-width: 575.98px) {
  .app-topbar {
    padding:
      0
      1rem;
  }

  .dashboard-content {
    padding: 1rem;
  }

  .page-heading-subtitle {
    display: none;
  }

  .notification-button {
    display: none;
  }

  .user-dropdown-button {
    padding:
      0.3rem;
  }

  .user-dropdown-button::after {
    display: none;
  }
}
</style>

<style>
/*
|--------------------------------------------------------------------------
| Global body lock for mobile sidebar
|--------------------------------------------------------------------------
*/

body.sidebar-mobile-active {
  overflow: hidden;
}
</style>
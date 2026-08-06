<template>
  <main class="login-page">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">
          <div class="text-center mb-4">
            <div class="brand-icon mx-auto mb-3">
              <i class="bi bi-shop"></i>
            </div>

            <h1 class="h3 fw-bold mb-2">
              Restaurant Management System
            </h1>

            <p class="text-secondary mb-0">
              Sign in to manage restaurant operations
            </p>
          </div>

          <div class="card login-card">
            <div class="card-body p-4 p-lg-5">
              <div
                v-if="errorMessage"
                class="alert alert-danger"
                role="alert"
              >
                <i class="bi bi-exclamation-circle me-2"></i>
                {{ errorMessage }}
              </div>

              <form @submit.prevent="handleLogin">
                <div class="mb-3">
                  <label
                    for="login"
                    class="form-label fw-semibold"
                  >
                    Email or Username
                  </label>

                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-person"></i>
                    </span>

                    <input
                      id="login"
                      v-model.trim="form.login"
                      type="text"
                      class="form-control"
                      :class="{ 'is-invalid': fieldErrors.login }"
                      placeholder="Enter email or username"
                      autocomplete="username"
                      autofocus
                    />

                    <div
                      v-if="fieldErrors.login"
                      class="invalid-feedback"
                    >
                      {{ fieldErrors.login }}
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <label
                    for="password"
                    class="form-label fw-semibold"
                  >
                    Password
                  </label>

                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-lock"></i>
                    </span>

                    <input
                      id="password"
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control"
                      :class="{ 'is-invalid': fieldErrors.password }"
                      placeholder="Enter password"
                      autocomplete="current-password"
                    />

                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="showPassword = !showPassword"
                    >
                      <i
                        :class="
                          showPassword
                            ? 'bi bi-eye-slash'
                            : 'bi bi-eye'
                        "
                      ></i>
                    </button>

                    <div
                      v-if="fieldErrors.password"
                      class="invalid-feedback"
                    >
                      {{ fieldErrors.password }}
                    </div>
                  </div>
                </div>

                <button
                  type="submit"
                  class="btn btn-primary w-100 mt-2"
                  :disabled="authStore.loading"
                >
                  <span
                    v-if="authStore.loading"
                    class="spinner-border spinner-border-sm me-2"
                  ></span>

                  {{
                    authStore.loading
                      ? 'Signing in...'
                      : 'Sign In'
                  }}
                </button>
              </form>

              <hr class="my-4" />

              <div class="small text-secondary">
                <p class="fw-semibold mb-2">
                  Development accounts
                </p>

                <div>Admin: admin / Admin@12345</div>
                <div>Manager: manager / Manager@12345</div>
                <div>Chef: chef / Chef@12345</div>
              </div>
            </div>
          </div>

          <p class="text-center text-secondary small mt-4">
            &copy; {{ currentYear }} Restaurant Management System
          </p>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const showPassword = ref(false)
const errorMessage = ref('')
const fieldErrors = ref({})

const form = reactive({
  login: '',
  password: '',
})

const currentYear = computed(() => new Date().getFullYear())

async function handleLogin() {
  errorMessage.value = ''
  fieldErrors.value = {}

  if (!form.login || !form.password) {
    if (!form.login) {
      fieldErrors.value.login =
        'Email or username is required.'
    }

    if (!form.password) {
      fieldErrors.value.password = 'Password is required.'
    }

    return
  }

  const result = await authStore.login({
    login: form.login,
    password: form.password,
    deviceName: 'RMS Vue Client',
  })

  if (!result.success) {
    errorMessage.value = result.message

    fieldErrors.value = {
      login: result.errors?.login?.[0],
      password: result.errors?.password?.[0],
    }

    return
  }

  const redirectPath =
    typeof route.query.redirect === 'string'
      ? route.query.redirect
      : authStore.getDefaultRoute()

  await router.replace(redirectPath)
}
</script>

<style scoped>
.login-page {
  display: flex;
  min-height: 100vh;
  align-items: center;
  padding: 2rem 0;
  background:
    radial-gradient(
      circle at top left,
      rgba(13, 110, 253, 0.14),
      transparent 35%
    ),
    #f4f6f9;
}

.login-card {
  overflow: hidden;
}

.brand-icon {
  display: flex;
  width: 64px;
  height: 64px;
  align-items: center;
  justify-content: center;
  border-radius: 1rem;
  background: #0d6efd;
  color: white;
  font-size: 1.75rem;
}

.input-group-text {
  min-width: 46px;
  justify-content: center;
}
</style>
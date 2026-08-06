<script setup>
const applicationName = 'Restaurant Management System'
</script>

<template>
  <main class="min-vh-100 bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
      <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="#">
          <i class="bi bi-shop me-2"></i>
          RMS
        </a>

        <span class="badge text-bg-success">
          Foundation Ready
        </span>
      </div>
    </nav>

    <section class="container py-5">
      <div class="row justify-content-center">
        <div class="col-lg-9">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
              <div class="d-flex align-items-center gap-3 mb-4">
                <div class="feature-icon">
                  <i class="bi bi-cup-hot"></i>
                </div>

                <div>
                  <h1 class="h3 mb-1">{{ applicationName }}</h1>
                  <p class="text-secondary mb-0">
                    Laravel REST API and Vue 3 frontend
                  </p>
                </div>
              </div>

              <div class="alert alert-success mb-4">
                <i class="bi bi-check-circle-fill me-2"></i>
                Vue frontend has been configured successfully.
              </div>

              <div class="row g-3">
                <div class="col-md-4">
                  <div class="module-card">
                    <i class="bi bi-server"></i>
                    <h2>Laravel API</h2>
                    <p>Backend business logic and database management.</p>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="module-card">
                    <i class="bi bi-window-sidebar"></i>
                    <h2>Vue 3</h2>
                    <p>Responsive user interface and application screens.</p>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="module-card">
                    <i class="bi bi-database"></i>
                    <h2>MySQL</h2>
                    <p>Restaurant operational and financial data storage.</p>
                  </div>
                </div>
              </div>

              <hr class="my-4">

              <p class="small text-secondary mb-0">
                Next step: MySQL database configuration and initial system
                database structure.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>
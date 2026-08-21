<template>
  <section class="recipe-mapping-section">

    <!-- ================================================================ -->
    <!-- HEADER -->
    <!-- ================================================================ -->

    <header class="recipe-mapping-header">

      <div>
        <h3>
          Recipe Mapping
        </h3>

        <p>
          Map Menu Items and Add-ons to the Raw Materials required for preparation.
        </p>
      </div>


      <div class="recipe-mapping-header-actions">

        <button
          v-if="canManageInventory"
          type="button"
          class="btn btn-primary btn-sm"
          :disabled="
            busy
            ||
            availableTargetCount === 0
          "
          @click="openAddForm"
        >
          <i
            class="bi bi-plus-lg me-2"
            aria-hidden="true"
          ></i>

          Add Recipe Mapping
        </button>


        <button
          type="button"
          class="btn btn-outline-secondary btn-sm"
          :disabled="busy"
          @click="refreshSection"
        >
          <i
            class="bi bi-arrow-clockwise me-2"
            aria-hidden="true"
          ></i>

          Refresh
        </button>

      </div>

    </header>


    <!-- ================================================================ -->
    <!-- PERMISSION -->
    <!-- ================================================================ -->

    <div
      v-if="!canViewInventory"
      class="alert alert-danger mb-0"
      role="alert"
    >
      You do not have permission to view recipe mappings.
    </div>


    <template v-else>

      <!-- ============================================================ -->
      <!-- ERROR -->
      <!-- ============================================================ -->

      <div
        v-if="errorMessage"
        class="
          alert
          alert-danger
          d-flex
          align-items-start
          gap-2
          mb-3
        "
        role="alert"
      >

        <i
          class="bi bi-exclamation-triangle mt-1"
          aria-hidden="true"
        ></i>

        <div class="flex-grow-1">
          {{ errorMessage }}
        </div>

        <button
          type="button"
          class="btn-close"
          aria-label="Dismiss error"
          @click="errorMessage = ''"
        ></button>

      </div>


      <!-- ============================================================ -->
      <!-- SUCCESS -->
      <!-- ============================================================ -->

      <div
        v-if="successMessage"
        class="
          alert
          alert-success
          d-flex
          align-items-start
          gap-2
          mb-3
        "
        role="status"
      >

        <i
          class="bi bi-check-circle mt-1"
          aria-hidden="true"
        ></i>

        <div class="flex-grow-1">
          {{ successMessage }}
        </div>

        <button
          type="button"
          class="btn-close"
          aria-label="Dismiss success message"
          @click="successMessage = ''"
        ></button>

      </div>


      <!-- ============================================================ -->
      <!-- ADD / EDIT FORM -->
      <!-- ============================================================ -->

      <section
        v-if="showForm"
        class="recipe-mapping-form-card"
        aria-labelledby="recipe-mapping-form-title"
      >

        <!-- ---------------------------------------------------------- -->
        <!-- FORM HEADER -->
        <!-- ---------------------------------------------------------- -->

        <div class="recipe-mapping-form-header">

          <div>

            <span class="recipe-mapping-eyebrow">
              {{
                formMode === 'edit'
                  ? 'Edit Mapping'
                  : 'New Mapping'
              }}
            </span>

            <h4 id="recipe-mapping-form-title">
              {{
                formMode === 'edit'
                  ? 'Edit Recipe Mapping'
                  : 'Add Recipe Mapping'
              }}
            </h4>

            <p>
              Define the Raw Materials required for one unit of the selected target.
            </p>

          </div>


          <button
            type="button"
            class="btn-close"
            aria-label="Close recipe form"
            :disabled="
              saving
              ||
              recipeLoading
            "
            @click="cancelForm"
          ></button>

        </div>


        <!-- ---------------------------------------------------------- -->
        <!-- RECIPE LOADING -->
        <!-- ---------------------------------------------------------- -->

        <div
          v-if="recipeLoading"
          class="
            recipe-mapping-loading
            recipe-mapping-loading-large
          "
          role="status"
        >

          <span
            class="spinner-border"
            aria-hidden="true"
          ></span>

          <span>
            Loading recipe mapping...
          </span>

        </div>


        <template v-else>

          <!-- ======================================================== -->
          <!-- TARGET SELECTOR -->
          <!-- ======================================================== -->

          <div class="recipe-mapping-target-block">

            <!-- ------------------------------------------------------ -->
            <!-- TARGET TYPE -->
            <!-- ------------------------------------------------------ -->

            <div class="recipe-mapping-target-type-field">

              <label
                for="recipe-target-type"
                class="form-label fw-semibold"
              >
                Item Type

                <span class="text-danger">
                  *
                </span>
              </label>


              <select
                id="recipe-target-type"
                v-model="selectedTargetType"
                class="form-select"
                :class="{
                  'is-invalid':
                    Boolean(
                      fieldError('target_type'),
                    ),
                }"
                :disabled="
                  saving
                  ||
                  recipeLoading
                  ||
                  formMode === 'edit'
                "
                @change="handleTargetTypeChange"
              >

                <option value="">
                  Select Item Type
                </option>

                <option value="menu_item">
                  Menu Item
                </option>

                <option value="add_on">
                  Add-on
                </option>

              </select>


              <div
                v-if="fieldError('target_type')"
                class="invalid-feedback d-block"
              >
                {{ fieldError('target_type') }}
              </div>

            </div>


            <!-- ------------------------------------------------------ -->
            <!-- TARGET PICKER -->
            <!-- ------------------------------------------------------ -->

            <div class="recipe-mapping-target-picker">

              <label class="form-label fw-semibold">
                {{
                  selectedTargetType === 'add_on'
                    ? 'Add-on'
                    : 'Menu Item'
                }}

                <span class="text-danger">
                  *
                </span>
              </label>


              <!-- Search -->

              <div class="recipe-mapping-target-search">

                <i
                  class="bi bi-search"
                  aria-hidden="true"
                ></i>

                <input
                  v-model.trim="targetSearchQuery"
                  type="search"
                  class="form-control"
                  :placeholder="
                    selectedTargetType === 'add_on'
                      ? 'Search add-on...'
                      : 'Search menu item...'
                  "
                  autocomplete="off"
                  :disabled="
                    saving
                    ||
                    recipeLoading
                    ||
                    !selectedTargetType
                    ||
                    formMode === 'edit'
                  "
                />

              </div>


              <!-- ==================================================== -->
              <!-- MENU ITEM OPTIONS -->
              <!-- ==================================================== -->

              <div
                v-if="
                  selectedTargetType === 'menu_item'
                  &&
                  formMode !== 'edit'
                "
                class="recipe-mapping-target-options"
              >

                <button
                  v-for="
                    option
                    in
                    filteredMenuItemTargets
                  "
                  :key="`menu-item-${option.id}`"
                  type="button"
                  class="recipe-mapping-target-option"
                  :class="{
                    'is-selected':
                      selectedTarget?.target_type === 'menu_item'
                      &&
                      Number(
                        selectedTarget?.target_id,
                      )
                      ===
                      Number(option.id),
                  }"
                  :disabled="
                    !menuItemCanBeSelected(
                      option,
                    )
                  "
                  @click="
                    selectMenuItemTarget(
                      option,
                    )
                  "
                >

                  <!-- Image -->

                  <span
                    class="
                      recipe-mapping-target-option-image
                      recipe-mapping-image-box
                    "
                  >

                    <img
                      v-if="
                        getMenuItemImage(
                          option,
                        )
                      "
                      :src="
                        getMenuItemImage(
                          option,
                        )
                      "
                      :alt="
                        `${menuItemName(option)} image`
                      "
                      class="recipe-mapping-target-img"
                      loading="lazy"
                      @error="
                        handleImageError
                      "
                    >

                    <span
                      v-else
                      class="
                        recipe-mapping-target-option-icon
                        recipe-mapping-image-fallback
                      "
                    >
                      <i
                        class="bi bi-cup-hot"
                        aria-hidden="true"
                      ></i>
                    </span>

                  </span>


                  <!-- Content -->

                  <span
                    class="
                      recipe-mapping-target-option-content
                    "
                  >

                    <strong>
                      {{ menuItemName(option) }}
                    </strong>

                    <small>
                      Menu Item
                    </small>

                    <small
                      v-if="
                        option.category_name
                      "
                      class="text-muted"
                    >
                      {{ option.category_name }}
                    </small>

                  </span>


                  <!-- Recipe Badge -->

                  <span
                    v-if="
                      menuItemHasNoVariantRecipe(
                        option.id,
                      )
                    "
                    class="badge text-bg-secondary"
                  >
                    Direct recipe configured
                  </span>

                </button>


                <div
                  v-if="
                    filteredMenuItemTargets.length === 0
                  "
                  class="recipe-mapping-target-options-empty"
                >
                  No menu items found.
                </div>

              </div>


              <!-- ==================================================== -->
              <!-- ADD-ON OPTIONS -->
              <!-- ==================================================== -->

              <div
                v-else-if="
                  selectedTargetType === 'add_on'
                  &&
                  formMode !== 'edit'
                "
                class="recipe-mapping-target-options"
              >

                <button
                  v-for="
                    option
                    in
                    filteredAddOnTargets
                  "
                  :key="`add-on-${option.id}`"
                  type="button"
                  class="recipe-mapping-target-option"
                  :class="{
                    'is-selected':
                      selectedTarget?.target_type === 'add_on'
                      &&
                      Number(
                        selectedTarget?.target_id,
                      )
                      ===
                      Number(option.id),
                  }"
                  :disabled="
                    addOnHasRecipe(
                      option.id,
                    )
                  "
                  @click="
                    selectAddOnTarget(
                      option,
                    )
                  "
                >

                  <!-- Add-on icon -->

                  <span
                    class="
                      recipe-mapping-target-option-image
                      recipe-mapping-image-box
                    "
                  >

                    <span
                      class="
                        recipe-mapping-target-option-icon
                        recipe-mapping-image-fallback
                      "
                    >
                      <i
                        class="bi bi-plus-circle"
                        aria-hidden="true"
                      ></i>
                    </span>

                  </span>


                  <!-- Content -->

                  <span
                    class="
                      recipe-mapping-target-option-content
                    "
                  >

                    <strong>
                      {{ addOnName(option) }}
                    </strong>

                    <small>
                      Add-on
                    </small>

                  </span>


                  <!-- Recipe Badge -->

                  <span
                    v-if="
                      addOnHasRecipe(
                        option.id,
                      )
                    "
                    class="badge text-bg-secondary"
                  >
                    Recipe configured
                  </span>

                </button>


                <div
                  v-if="
                    filteredAddOnTargets.length === 0
                  "
                  class="recipe-mapping-target-options-empty"
                >
                  No add-ons found.
                </div>

              </div>


              <!-- ==================================================== -->
              <!-- SELECTED TARGET -->
              <!-- ==================================================== -->

              <div
                v-if="selectedTarget"
                class="recipe-mapping-selected-target"
              >

                <!-- Image -->

                <div
                  class="
                    recipe-mapping-selected-target-image
                    recipe-mapping-selected-image-box
                  "
                >

                  <img
                    v-if="
                      selectedTarget.target_type === 'menu_item'
                      &&
                      getMenuItemImage(
                        selectedTarget,
                      )
                    "
                    :src="
                      getMenuItemImage(
                        selectedTarget,
                      )
                    "
                    :alt="
                      `${selectedTarget.target_name} image`
                    "
                    class="recipe-mapping-selected-target-img"
                    @error="
                      handleImageError
                    "
                  >

                  <span
                    v-else
                    class="
                      recipe-mapping-selected-target-icon
                      recipe-mapping-image-fallback
                    "
                  >
                    <i
                      :class="
                        selectedTarget.target_type === 'add_on'
                          ? 'bi bi-plus-circle'
                          : 'bi bi-cup-hot'
                      "
                      aria-hidden="true"
                    ></i>
                  </span>

                </div>


                <!-- Content -->

                <div
                  class="
                    recipe-mapping-selected-target-content
                  "
                >

                  <strong>
                    {{ selectedTarget.target_name }}
                  </strong>

                  <span>
                    {{
                      selectedTarget.target_type === 'add_on'
                        ? 'Add-on'
                        : 'Menu Item'
                    }}
                  </span>

                  <small
                    v-if="
                      selectedTarget.target_type === 'menu_item'
                      &&
                      selectedTarget.category_name
                    "
                  >
                    {{ selectedTarget.category_name }}
                  </small>

                </div>


                <span
                  v-if="
                    selectedTarget.target_type === 'menu_item'
                  "
                  class="badge text-bg-primary"
                >
                  Menu Item
                </span>

                <span
                  v-else
                  class="badge text-bg-info"
                >
                  Add-on
                </span>

              </div>


              <!-- Target Error -->

              <div
                v-if="fieldError('target_id')"
                class="invalid-feedback d-block"
              >
                {{ fieldError('target_id') }}
              </div>

            </div>


            <!-- ======================================================== -->
            <!-- VARIANT -->
            <!-- ======================================================== -->

            <div
              v-if="
                selectedTargetType === 'menu_item'
              "
              class="recipe-mapping-variant-field mt-3"
            >

              <label
                for="recipe-target-variant"
                class="form-label fw-semibold"
              >
                Variant

                <span
                  v-if="
                    availableVariantOptions.length > 0
                  "
                  class="text-danger"
                >
                  *
                </span>
              </label>


              <select
                id="recipe-target-variant"
                v-model="selectedVariantId"
                class="form-select"
                :class="{
                  'is-invalid':
                    Boolean(
                      fieldError('variant_id'),
                    ),
                }"
                :disabled="
                  saving
                  ||
                  recipeLoading
                  ||
                  !selectedTarget
                  ||
                  formMode === 'edit'
                "
                @change="handleVariantChange"
              >

                <option value="">
                  {{
                    variantLoading
                      ? 'Loading variants...'
                      : availableVariantOptions.length > 0
                        ? 'Select Variant'
                        : 'No Variant / Direct Recipe'
                  }}
                </option>


                <option
                  v-for="
                    variant
                    in
                    availableVariantOptions
                  "
                  :key="variant.id"
                  :value="String(variant.id)"
                  :disabled="
                    variantOptionDisabled(
                      variant,
                    )
                  "
                >
                  {{ variant.variant_name }}

                  {{
                    variantOptionDisabled(
                      variant,
                    )
                      ? ' — Recipe configured'
                      : ''
                  }}
                </option>

              </select>


              <div
                v-if="fieldError('variant_id')"
                class="invalid-feedback d-block"
              >
                {{ fieldError('variant_id') }}
              </div>


              <div
                v-if="
                  selectedTarget
                  &&
                  selectedTargetType === 'menu_item'
                "
                class="recipe-mapping-target-help"
              >

                <span
                  v-if="
                    availableVariantOptions.length > 0
                  "
                >
                  Select a variant to create a variant-specific recipe.
                </span>

                <span
                  v-else
                >
                  This menu item has no variants, so the recipe applies directly to the menu item.
                </span>

              </div>

            </div>

          </div>


          <!-- ======================================================== -->
          <!-- INGREDIENTS -->
          <!-- ======================================================== -->

          <div class="recipe-mapping-form-body">

            <div
              class="
                recipe-mapping-form-section-heading
              "
            >

              <div>

                <h5>
                  Ingredients
                </h5>

                <p>
                  Quantity supports up to 4 decimal places. Unit comes from the Raw Material base unit.
                </p>

              </div>


              <span class="recipe-mapping-count">
                {{ ingredients.length }} / 200
              </span>

            </div>


            <!-- Empty -->

            <div
              v-if="ingredients.length === 0"
              class="recipe-mapping-form-empty"
            >

              <i
                class="bi bi-basket2"
                aria-hidden="true"
              ></i>

              <strong>
                No ingredients added
              </strong>

              <span>
                Add at least one Raw Material before saving.
              </span>

              <button
                v-if="canManageInventory"
                type="button"
                class="btn btn-outline-primary btn-sm"
                :disabled="
                  saving
                  ||
                  rawMaterials.length === 0
                "
                @click="addIngredient"
              >

                <i
                  class="bi bi-plus-lg me-2"
                  aria-hidden="true"
                ></i>

                Add Ingredient

              </button>

            </div>


            <!-- Ingredient rows -->

            <div
              v-else
              class="recipe-mapping-editor-list"
            >

              <article
                v-for="
                  (
                    ingredient,
                    index
                  ) in ingredients
                "
                :key="ingredient._key"
                class="recipe-mapping-ingredient-row"
              >

                <!-- Number -->

                <div
                  class="
                    recipe-mapping-ingredient-index
                  "
                >
                  {{ index + 1 }}
                </div>


                <!-- Raw Material -->

                <div
                  class="
                    recipe-mapping-ingredient-field
                    recipe-mapping-material-field
                  "
                >

                  <label
                    :for="
                      `recipe-material-${ingredient._key}`
                    "
                    class="form-label"
                  >
                    Raw Material

                    <span class="text-danger">
                      *
                    </span>
                  </label>


                  <select
                    :id="
                      `recipe-material-${ingredient._key}`
                    "
                    v-model="
                      ingredient.raw_material_id
                    "
                    class="form-select"
                    :class="{
                      'is-invalid':
                        Boolean(
                          rowError(
                            index,
                            'raw_material_id',
                          ),
                        ),
                    }"
                    :disabled="saving"
                    @change="
                      handleRawMaterialChange(
                        ingredient,
                        index,
                      )
                    "
                  >

                    <option value="">
                      Select raw material
                    </option>


                    <option
                      v-for="
                        material
                        in
                        rawMaterials
                      "
                      :key="material.id"
                      :value="
                        String(
                          material.id,
                        )
                      "
                      :disabled="
                        materialUsedByOtherRow(
                          material.id,
                          index,
                        )
                      "
                    >
                      {{
                        rawMaterialLabel(
                          material,
                        )
                      }}
                    </option>

                  </select>


                  <div
                    v-if="
                      rowError(
                        index,
                        'raw_material_id',
                      )
                    "
                    class="invalid-feedback d-block"
                  >
                    {{
                      rowError(
                        index,
                        'raw_material_id',
                      )
                    }}
                  </div>

                </div>


                <!-- Quantity -->

                <div
                  class="
                    recipe-mapping-ingredient-field
                    recipe-mapping-quantity-field
                  "
                >

                  <label
                    :for="
                      `recipe-quantity-${ingredient._key}`
                    "
                    class="form-label"
                  >
                    Quantity

                    <span class="text-danger">
                      *
                    </span>
                  </label>


                  <div class="input-group">

                    <input
                      :id="
                        `recipe-quantity-${ingredient._key}`
                      "
                      :value="
                        ingredient.quantity
                      "
                      type="text"
                      inputmode="decimal"
                      autocomplete="off"
                      class="form-control"
                      :class="{
                        'is-invalid':
                          Boolean(
                            rowError(
                              index,
                              'quantity',
                            ),
                          ),
                      }"
                      placeholder="0.0000"
                      :disabled="saving"
                      @input="
                        handleQuantityInput(
                          ingredient,
                          $event,
                        )
                      "
                      @paste="
                        handleQuantityPaste(
                          ingredient,
                          $event,
                        )
                      "
                    />

                    <span class="input-group-text">
                      {{
                        ingredient.unit
                        ||
                        'unit'
                      }}
                    </span>

                  </div>


                  <div
                    v-if="
                      rowError(
                        index,
                        'quantity',
                      )
                    "
                    class="invalid-feedback d-block"
                  >
                    {{
                      rowError(
                        index,
                        'quantity',
                      )
                    }}
                  </div>

                </div>


                <!-- Stock -->

                <div
                  class="
                    recipe-mapping-ingredient-field
                    recipe-mapping-stock-field
                  "
                >

                  <label class="form-label">
                    Restaurant Stock
                  </label>


                  <template
                    v-if="
                      ingredient.raw_material_id
                    "
                  >

                    <div class="recipe-mapping-stock-line">

                      <strong>
                        {{
                          stockQuantityLabel(
                            ingredient,
                          )
                        }}
                      </strong>


                      <span
                        class="badge"
                        :class="
                          stockBadgeClass(
                            ingredient,
                          )
                        "
                      >
                        {{
                          stockStatusLabel(
                            ingredient,
                          )
                        }}
                      </span>

                    </div>


                    <small
                      v-if="
                        ingredientHasQuantity(
                          ingredient,
                        )
                        &&
                        !sufficientForOne(
                          ingredient,
                        )
                      "
                      class="
                        recipe-mapping-stock-warning
                      "
                    >

                      <i
                        class="bi bi-exclamation-triangle"
                        aria-hidden="true"
                      ></i>

                      Not enough for one unit

                    </small>

                  </template>


                  <span
                    v-else
                    class="text-muted"
                  >
                    —
                  </span>

                </div>


                <!-- Notes -->

                <div
                  class="
                    recipe-mapping-ingredient-field
                    recipe-mapping-notes-field
                  "
                >

                  <label
                    :for="
                      `recipe-notes-${ingredient._key}`
                    "
                    class="form-label"
                  >
                    Notes
                  </label>


                  <textarea
                    :id="
                      `recipe-notes-${ingredient._key}`
                    "
                    v-model="
                      ingredient.notes
                    "
                    class="form-control"
                    :class="{
                      'is-invalid':
                        Boolean(
                          rowError(
                            index,
                            'notes',
                          ),
                        ),
                    }"
                    rows="2"
                    maxlength="2000"
                    placeholder="Optional preparation note"
                    :disabled="saving"
                    @input="
                      clearRowError(
                        index,
                        'notes',
                      )
                    "
                  ></textarea>


                  <div
                    v-if="
                      rowError(
                        index,
                        'notes',
                      )
                    "
                    class="invalid-feedback d-block"
                  >
                    {{
                      rowError(
                        index,
                        'notes',
                      )
                    }}
                  </div>

                </div>


                <!-- Remove -->

                <div
                  class="
                    recipe-mapping-ingredient-action
                  "
                >

                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    :disabled="saving"
                    :aria-label="
                      `Remove ingredient ${index + 1}`
                    "
                    title="Remove ingredient"
                    @click="
                      removeIngredient(
                        index,
                      )
                    "
                  >

                    <i
                      class="bi bi-trash"
                      aria-hidden="true"
                    ></i>

                  </button>

                </div>

              </article>

            </div>


            <!-- Add ingredient -->

            <div
              class="
                recipe-mapping-add-ingredient-bar
              "
            >

              <button
                v-if="canManageInventory"
                type="button"
                class="btn btn-outline-primary btn-sm"
                :disabled="
                  saving
                  ||
                  !canAddIngredient
                "
                @click="addIngredient"
              >

                <i
                  class="bi bi-plus-lg me-2"
                  aria-hidden="true"
                ></i>

                Add Ingredient

              </button>


              <small class="text-muted">
                Maximum 200 ingredients per recipe.
              </small>

            </div>

          </div>


          <!-- ======================================================== -->
          <!-- FOOTER -->
          <!-- ======================================================== -->

          <footer
            class="
              recipe-mapping-form-footer
            "
          >

            <div
              class="
                recipe-mapping-footer-note
              "
            >

              <i
                class="bi bi-info-circle"
                aria-hidden="true"
              ></i>

              <span>
                Saving changes the recipe definition only.
                Stock is deducted when the kitchen starts preparing an order.
              </span>

            </div>


            <div
              class="
                recipe-mapping-form-actions
              "
            >

              <button
                type="button"
                class="btn btn-outline-secondary"
                :disabled="saving"
                @click="cancelForm"
              >
                Cancel
              </button>


              <button
                type="button"
                class="btn btn-primary"
                :disabled="
                  saving
                  ||
                  !hasChanges
                  ||
                  !recipeIsValid
                "
                @click="saveRecipe"
              >

                <span
                  v-if="saving"
                  class="
                    spinner-border
                    spinner-border-sm
                    me-2
                  "
                  aria-hidden="true"
                ></span>


                <i
                  v-else
                  class="bi bi-check2-circle me-2"
                  aria-hidden="true"
                ></i>

                Save Recipe

              </button>

            </div>

          </footer>

        </template>

      </section>


      <!-- ============================================================ -->
      <!-- CONFIGURED MAPPING LIST -->
      <!-- ============================================================ -->

      <section
        class="
          recipe-mapping-list-card
        "
      >

        <div
          class="
            recipe-mapping-list-header
          "
        >

          <div>

            <h4>
              Configured Mappings
            </h4>

            <p>
              {{ filteredRecipeMappings.length }}
              of
              {{ recipeMappings.length }}
              mappings shown
            </p>

          </div>


          <div
            class="
              recipe-mapping-search
            "
          >

            <i
              class="bi bi-search"
              aria-hidden="true"
            ></i>

            <input
              v-model.trim="searchQuery"
              type="search"
              class="form-control"
              placeholder="Search item, add-on or ingredient"
              autocomplete="off"
              :disabled="foundationLoading"
            />

          </div>

        </div>


        <!-- Loading -->

        <div
          v-if="foundationLoading"
          class="
            recipe-mapping-loading
            recipe-mapping-loading-large
          "
          role="status"
        >

          <span
            class="spinner-border"
            aria-hidden="true"
          ></span>

          <span>
            Loading recipe mappings...
          </span>

        </div>


        <!-- No mapping -->

        <div
          v-else-if="
            recipeMappings.length === 0
          "
          class="
            recipe-mapping-list-empty
          "
        >

          <i
            class="bi bi-diagram-3"
            aria-hidden="true"
          ></i>

          <strong>
            No recipe mappings configured
          </strong>

          <p>
            Add a mapping to connect a Menu Item or Add-on with its Raw Materials.
          </p>

          <button
            v-if="
              canManageInventory
              &&
              availableTargetCount > 0
            "
            type="button"
            class="btn btn-primary btn-sm"
            @click="openAddForm"
          >

            <i
              class="bi bi-plus-lg me-2"
              aria-hidden="true"
            ></i>

            Add Recipe Mapping

          </button>

        </div>


        <!-- Search empty -->

        <div
          v-else-if="
            filteredRecipeMappings.length === 0
          "
          class="
            recipe-mapping-list-empty
          "
        >

          <i
            class="bi bi-search"
            aria-hidden="true"
          ></i>

          <strong>
            No matching recipe mappings
          </strong>

          <p>
            Try a different search term.
          </p>

        </div>


        <!-- ========================================================== -->
        <!-- TABLE -->
        <!-- ========================================================== -->

        <div
          v-else
          class="
            recipe-mapping-list-table-wrap
          "
        >

          <table
            class="
              table
              align-middle
              mb-0
              recipe-mapping-list-table
            "
          >

            <thead>

              <tr>

                <th>
                  Item / Add-on
                </th>

                <th>
                  Ingredients
                </th>

                <th
                  v-if="canManageInventory"
                  class="
                    recipe-mapping-list-action-column
                  "
                >
                  Action
                </th>

              </tr>

            </thead>


            <tbody>

              <tr
                v-for="
                  mapping
                  in
                  filteredRecipeMappings
                "
                :key="
                  mappingKey(
                    mapping,
                  )
                "
              >

                <!-- ================================================== -->
                <!-- TARGET CELL -->
                <!-- ================================================== -->

                <td>

                  <div
                    class="
                      recipe-mapping-target-cell
                    "
                  >

                    <!-- Image -->

                    <div
                      class="
                        recipe-mapping-target-icon
                        recipe-mapping-list-image-box
                      "
                    >

                      <img
                        v-if="
                          mapping.target_type === 'menu_item'
                          &&
                          getMappingImage(
                            mapping,
                          )
                        "
                        :src="
                          getMappingImage(
                            mapping,
                          )
                        "
                        :alt="
                          `${mapping.target_name || 'Menu item'} image`
                        "
                        class="
                          recipe-mapping-list-target-img
                        "
                        loading="lazy"
                        @error="
                          handleImageError
                        "
                      >

                      <span
                        v-else
                        class="
                          recipe-mapping-image-fallback
                        "
                      >

                        <i
                          :class="
                            mapping.target_type === 'add_on'
                              ? 'bi bi-plus-circle'
                              : 'bi bi-cup-hot'
                          "
                          aria-hidden="true"
                        ></i>

                      </span>

                    </div>


                    <!-- Content -->

                    <div
                      class="
                        recipe-mapping-target-content
                      "
                    >

                      <div
                        class="
                          recipe-mapping-target-title
                        "
                      >
                        {{
                          mapping.target_name
                          ||
                          targetFallbackName(
                            mapping,
                          )
                        }}
                      </div>


                      <div
                        class="
                          recipe-mapping-target-meta
                        "
                      >

                        <span
                          class="badge"
                          :class="
                            mapping.target_type === 'add_on'
                              ? 'text-bg-info'
                              : 'text-bg-primary'
                          "
                        >
                          {{
                            mapping.target_type === 'add_on'
                              ? 'Add-on'
                              : 'Menu Item'
                          }}
                        </span>
                        <span
  v-if="
    mapping.target_type === 'menu_item'
    &&
    mapping.variant_name
  "
  class="badge text-bg-warning"
>
  {{ mapping.variant_name }}
</span>

                        <span
                          v-if="
                            mapping.variant_id
                          "
                          class="badge text-bg-dark"
                        >
                          {{
                            mapping.variant_name
                            ||
                            `Variant #${mapping.variant_id}`
                          }}
                        </span>


                        <span
                          class="badge"
                          :class="
                            mapping.is_available
                              ? 'text-bg-success'
                              : 'text-bg-secondary'
                          "
                        >
                          {{
                            mapping.is_available
                              ? 'Available'
                              : 'Unavailable'
                          }}
                        </span>

                      </div>

                    </div>

                  </div>

                </td>


                <!-- ================================================== -->
                <!-- INGREDIENTS -->
                <!-- ================================================== -->

                <td>

                  <div
                    class="
                      recipe-mapping-summary-list
                    "
                  >

                    <span
                      v-for="
                        ingredient
                        in
                        mapping.ingredients
                        ||
                        []
                      "
                      :key="
                        ingredient.id
                        ||
                        `${mappingKey(mapping)}-${ingredient.raw_material_id}`
                      "
                      class="
                        recipe-mapping-summary-chip
                      "
                    >

                      <strong>
                        {{
                          ingredientName(
                            ingredient,
                          )
                        }}
                      </strong>

                      <span>
                        {{
                          ingredientQuantityLabel(
                            ingredient,
                          )
                        }}
                      </span>

                    </span>


                    <span
                      v-if="
                        !mappingHasIngredients(
                          mapping,
                        )
                      "
                      class="text-muted"
                    >
                      No ingredients
                    </span>

                  </div>

                </td>


                <!-- ================================================== -->
                <!-- ACTIONS -->
                <!-- ================================================== -->

                <td
                  v-if="canManageInventory"
                  class="
                    recipe-mapping-list-actions
                  "
                >

                  <button
                    type="button"
                    class="btn btn-outline-primary btn-sm"
                    :disabled="busy"
                    :aria-label="
                      `Edit ${mapping.target_name || 'recipe mapping'}`
                    "
                    title="Edit recipe mapping"
                    @click="
                      editMapping(
                        mapping,
                      )
                    "
                  >

                    <i
                      class="bi bi-pencil-square"
                      aria-hidden="true"
                    ></i>

                  </button>


                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    :disabled="busy"
                    :aria-label="
                      `Delete ${mapping.target_name || 'recipe mapping'}`
                    "
                    title="Delete recipe mapping"
                    @click="
                      deleteMapping(
                        mapping,
                      )
                    "
                  >

                    <span
                      v-if="
                        deletingTargetKey
                        ===
                        mappingKey(
                          mapping,
                        )
                      "
                      class="
                        spinner-border
                        spinner-border-sm
                      "
                      aria-hidden="true"
                    ></span>


                    <i
                      v-else
                      class="bi bi-trash"
                      aria-hidden="true"
                    ></i>

                  </button>

                </td>

              </tr>

            </tbody>

          </table>

        </div>

      </section>

    </template>

  </section>
</template>

<script setup>
import {
  computed,
  onMounted,
  ref,
} from 'vue'

import {
  useAuthStore,
} from '@/stores/auth'

import api
  from '@/services/api'

import inventoryService
  from '@/services/inventoryService'

import '@/assets/css/inventory/recipe-mapping.css'


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

const authStore =
  useAuthStore()


const canViewInventory =
  computed(() => {

    if (
      typeof authStore.hasPermission
      !==
      'function'
    ) {
      return false
    }

    return Boolean(

      authStore.hasPermission(
        'inventory.view',
      )

      ||

      authStore.hasPermission(
        'inventory.manage',
      )

    )
  })


const canManageInventory =
  computed(() => {

    if (
      typeof authStore.hasPermission
      !==
      'function'
    ) {
      return false
    }

    return Boolean(
      authStore.hasPermission(
        'inventory.manage',
      ),
    )
  })


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const foundationLoading =
  ref(false)

const recipeLoading =
  ref(false)

const saving =
  ref(false)

const deletingTargetKey =
  ref('')

const errorMessage =
  ref('')

const successMessage =
  ref('')

const serverErrors =
  ref({})


const menuItems =
  ref([])

const addOns =
  ref([])

const rawMaterials =
  ref([])

const restaurantStocks =
  ref([])

const recipeMappings =
  ref([])


const searchQuery =
  ref('')

const showForm =
  ref(false)

const formMode =
  ref('add')


/*
|--------------------------------------------------------------------------
| Target Selection
|--------------------------------------------------------------------------
|
| selectedTargetType:
|     menu_item
|     add_on
|
| selectedTargetKey:
|     menu item ID only
|     add-on ID only
|
| Example:
|     selectedTargetType = menu_item
|     selectedTargetKey  = 5
|
| Variant stays separate:
|     selectedVariantId = 2
|
*/

const selectedTargetKey =
  ref('')

const selectedTargetType =
  ref('')

const selectedVariantId =
  ref('')


const targetSearchQuery =
  ref('')


const variants =
  ref([])

const variantLoading =
  ref(false)


const ingredients =
  ref([])

const savedSnapshot =
  ref('')


let rowSequence = 0

let recipeRequestSequence = 0


/*
|--------------------------------------------------------------------------
| Busy State
|--------------------------------------------------------------------------
*/

const busy =
  computed(() => {

    return (

      foundationLoading.value

      ||

      recipeLoading.value

      ||

      saving.value

      ||

      deletingTargetKey.value !== ''

    )
  })


/*
|--------------------------------------------------------------------------
| Target Options
|--------------------------------------------------------------------------
*/

const targetOptions =
  computed(() => {

    const menuOptions =
      menuItems.value.map(
        (item) => ({

          key:
            `menu_item:${Number(item.id)}`,

          target_type:
            'menu_item',

          target_id:
            Number(
              item.id,
            ),

          target_name:
            menuItemName(
              item,
            ),

          image_url:
            item?.image_url
            ??
            null,

          variants:
            Array.isArray(
              item?.variants,
            )
              ? item.variants
              : [],

          is_available:
            Boolean(
              item?.is_available,
            ),

        }),
      )


    const addOnOptions =
      addOns.value.map(
        (addOn) => ({

          key:
            `add_on:${Number(addOn.id)}`,

          target_type:
            'add_on',

          target_id:
            Number(
              addOn.id,
            ),

          target_name:
            addOnName(
              addOn,
            ),

          image_url:
            null,

          variants:
            [],

          is_available:
            Boolean(
              addOn?.is_available,
            ),

        }),
      )


    return [
      ...menuOptions,
      ...addOnOptions,
    ]
      .sort(
        (
          a,
          b,
        ) => {

          if (
            a.target_type
            !==
            b.target_type
          ) {

            return (
              a.target_type ===
              'menu_item'
            )
              ? -1
              : 1
          }


          return (
            a.target_name
              .localeCompare(
                b.target_name,
              )
          )
        },
      )
  })


/*
|--------------------------------------------------------------------------
| Recipe Mapping Identity
|--------------------------------------------------------------------------
|
| Direct Menu Item:
|     menu_item:5:0
|
| Variant:
|     menu_item:5:2
|
| Add-on:
|     add_on:3:0
|
*/

function recipeMappingIdentity(
  mapping,
) {

  const targetType =
    String(
      mapping?.target_type
      ||
      '',
    )


  const targetId =
    Number(
      mapping?.target_id
      ||
      0,
    )


  const variantId =
    targetType ===
    'menu_item'

      ? Number(
          mapping?.variant_id
          ||
          0,
        )

      : 0


  return (
    `${targetType}:`
    +
    `${targetId}:`
    +
    `${variantId}`
  )
}


/*
|--------------------------------------------------------------------------
| Configured Recipe Keys
|--------------------------------------------------------------------------
*/

const mappedRecipeKeys =
  computed(() => {

    return new Set(

      recipeMappings.value.map(
        (mapping) => {

          return recipeMappingIdentity(
            mapping,
          )
        },
      ),

    )
  })


/*
|--------------------------------------------------------------------------
| Selected Target
|--------------------------------------------------------------------------
*/

const selectedTarget =
  computed(() => {

    const type =
      String(
        selectedTargetType.value
        ||
        '',
      )
        .trim()


    const targetId =
      Number(
        selectedTargetKey.value
        ||
        0,
      )


    if (
      !type
      ||
      !Number.isInteger(
        targetId,
      )
      ||
      targetId <= 0
    ) {

      return null
    }


    if (
      type ===
      'menu_item'
    ) {

      const item =
        menuItems.value.find(
          (
            menuItem,
          ) => {

            return (
              Number(
                menuItem?.id,
              )
              ===
              targetId
            )
          },
        )


      if (
        !item
      ) {

        return null
      }


      return {

        key:
          `menu_item:${targetId}`,

        target_type:
          'menu_item',

        target_id:
          targetId,

        target_name:
          menuItemName(
            item,
          ),

        image_url:
          item?.image_url
          ??
          null,

        category_name:
          item?.category_name
          ??
          item?.category?.category_name
          ??
          null,

        variants:
          Array.isArray(item?.variants)
            ? item.variants
            : [],

        is_available:
          Boolean(
            item?.is_available,
          ),
      }
    }


    if (
      type ===
      'add_on'
    ) {

      const addOn =
        addOns.value.find(
          (
            item,
          ) => {

            return (
              Number(
                item?.id,
              )
              ===
              targetId
            )
          },
        )


      if (
        !addOn
      ) {

        return null
      }


      return {

        key:
          `add_on:${targetId}`,

        target_type:
          'add_on',

        target_id:
          targetId,

        target_name:
          addOnName(
            addOn,
          ),

        image_url:
          null,

        is_available:
          Boolean(
            addOn?.is_available,
          ),
      }
    }


    return null
  })


/*
|--------------------------------------------------------------------------
| Current Selected Recipe Identity
|--------------------------------------------------------------------------
*/

const selectedRecipeIdentity =
  computed(() => {

    if (
      !selectedTarget.value
    ) {

      return ''
    }


    const targetType =
      selectedTarget.value
        .target_type


    const targetId =
      Number(
        selectedTarget.value
          .target_id,
      )


    const variantId =
      targetType ===
      'menu_item'

        ? Number(
            selectedVariantId.value
            ||
            0,
          )

        : 0


    return (
      `${targetType}:${targetId}:${variantId}`
    )
  })


/*
|--------------------------------------------------------------------------
| Available Target Count
|--------------------------------------------------------------------------
*/

const availableTargetCount =
  computed(() => {

    return targetOptions.value
      .filter(
        (option) => {

          if (
            option.target_type ===
            'add_on'
          ) {

            return !recipeMappings.value.some(
              (mapping) => {

                return (
                  mapping?.target_type ===
                    'add_on'
                  &&
                  Number(
                    mapping?.target_id,
                  )
                  ===
                  Number(
                    option.target_id,
                  )
                )
              },
            )
          }


          const menuItemId =
            Number(
              option.target_id,
            )


          const hasDirectRecipe =
            recipeMappings.value.some(
              (mapping) => {

                return (
                  mapping?.target_type ===
                    'menu_item'
                  &&
                  Number(
                    mapping?.target_id,
                  )
                  ===
                  menuItemId
                  &&
                  !Number(
                    mapping?.variant_id
                    ||
                    0,
                  )
                )
              },
            )


          const item =
            menuItems.value.find(
              (menuItem) => {

                return (
                  Number(
                    menuItem?.id,
                  )
                  ===
                  menuItemId
                )
              },
            )


          const itemHasVariants =
            Array.isArray(
              item?.variants,
            )
            &&
            item.variants.length > 0


          return (
            !hasDirectRecipe
            ||
            itemHasVariants
          )
        },
      )
      .length
  })


/*
|--------------------------------------------------------------------------
| Filtered Menu Items
|--------------------------------------------------------------------------
*/

const filteredMenuItemTargets =
  computed(() => {

    const search =
      String(
        targetSearchQuery.value
        ||
        '',
      )
        .trim()
        .toLowerCase()


    const items =
      menuItems.value
        .slice()
        .sort(
          (
            a,
            b,
          ) => {

            return menuItemName(
              a,
            )
              .localeCompare(
                menuItemName(
                  b,
                ),
              )
          },
        )


    if (
      !search
    ) {

      return items
    }


    return items.filter(
      (
        item,
      ) => {

        const name =
          menuItemName(
            item,
          )
            .toLowerCase()


        return name.includes(
          search,
        )
      },
    )
  })


/*
|--------------------------------------------------------------------------
| Filtered Add-ons
|--------------------------------------------------------------------------
*/

const filteredAddOnTargets =
  computed(() => {

    const search =
      String(
        targetSearchQuery.value
        ||
        '',
      )
        .trim()
        .toLowerCase()


    const items =
      addOns.value
        .slice()
        .sort(
          (
            a,
            b,
          ) => {

            return addOnName(
              a,
            )
              .localeCompare(
                addOnName(
                  b,
                ),
              )
          },
        )


    if (
      !search
    ) {

      return items
    }


    return items.filter(
      (
        item,
      ) => {

        return addOnName(
          item,
        )
          .toLowerCase()
          .includes(
            search,
          )
      },
    )
  })


/*
|--------------------------------------------------------------------------
| Available Variant Options
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Available Variant Options
|--------------------------------------------------------------------------
|
| Variant options are taken directly from the selected Menu Item.
| This avoids depending on the separate global variants collection.
|
*/

const availableVariantOptions =
  computed(() => {

    if (
      selectedTargetType.value !==
      'menu_item'
    ) {
      return []
    }


    const menuItemId =
      Number(
        selectedTarget.value?.target_id
        ?? 0
      )


    if (
      !Number.isInteger(
        menuItemId
      )
      ||
      menuItemId <= 0
    ) {
      return []
    }


    const menuItem =
      menuItems.value.find(
        (item) => {

          return (
            Number(
              item?.id
            )
            ===
            menuItemId
          )
        }
      )


    if (
      !menuItem
    ) {
      return []
    }


    const menuItemVariants =
      Array.isArray(
        menuItem?.variants
      )
        ? menuItem.variants
        : []


    return menuItemVariants
      .filter(
        (variant) => {

          return (
            Number(
              variant?.menu_item_id
            )
            ===
            menuItemId
          )
          &&
          variant?.is_available !== false
        }
      )
      .sort(
        (
          a,
          b
        ) => {

          return String(
            a?.variant_name
            ?? ''
          ).localeCompare(
            String(
              b?.variant_name
              ?? ''
            )
          )
        }
      )
  })


/*
|--------------------------------------------------------------------------
| Mapping Search
|--------------------------------------------------------------------------
*/

const filteredRecipeMappings =
  computed(() => {

    const search =
      String(
        searchQuery.value
        ||
        '',
      )
        .trim()
        .toLowerCase()


    if (
      !search
    ) {

      return recipeMappings.value
    }


    return recipeMappings.value
      .filter(
        (
          mapping,
        ) => {

          const ingredientText =
            mappingHasIngredients(
              mapping,
            )

              ? mapping.ingredients
                  .map(
                    (
                      ingredient,
                    ) => {

                      return `${
                        ingredientName(
                          ingredient,
                        )
                      } ${
                        ingredientQuantityLabel(
                          ingredient,
                        )
                      }`
                    },
                  )
                  .join(' ')

              : ''


          const typeText =
            mapping?.target_type
            ===
            'add_on'

              ? 'add-on addon'

              : 'menu item'


          const variantText =
            String(
              mapping?.variant_name
              ||
              '',
            )


          return [

            mapping?.target_name,

            typeText,

            variantText,

            ingredientText,

          ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
            .includes(
              search,
            )
        },
      )
  })


/*
|--------------------------------------------------------------------------
| Ingredient Availability
|--------------------------------------------------------------------------
*/

const canAddIngredient =
  computed(() => {

    return (

      ingredients.value.length
      <
      200

      &&

      rawMaterials.value.length
      >
      0

    )
  })


/*
|--------------------------------------------------------------------------
| Dirty State
|--------------------------------------------------------------------------
*/

const currentSnapshot =
  computed(() => {

    return JSON.stringify({

      target_type:
        selectedTargetType.value,

      target_id:
        selectedTarget.value
          ?.target_id
          ??
          null,

      variant_id:
        selectedTargetType.value ===
        'menu_item'

          ? Number(
              selectedVariantId.value
              ||
              0,
            )
          : 0,

      ingredients:
        ingredients.value.map(
          (
            ingredient,
          ) => {

            return {

              raw_material_id:
                String(
                  ingredient
                    .raw_material_id
                  ||
                  '',
                ),

              quantity:
                normalizeQuantityForSnapshot(
                  ingredient.quantity,
                ),

              notes:
                String(
                  ingredient.notes
                  ||
                  '',
                )
                  .trim(),

            }
          },
        ),

    })
  })


const hasChanges =
  computed(() => {

    return (
      currentSnapshot.value
      !==
      savedSnapshot.value
    )
  })


/*
|--------------------------------------------------------------------------
| Recipe Validation
|--------------------------------------------------------------------------
*/

const recipeIsValid =
  computed(() => {

    if (
      !selectedTarget.value
      ||
      ingredients.value.length ===
      0
    ) {

      return false
    }


    /*
    |----------------------------------------------------------------------
    | Menu Item Variant Validation
    |----------------------------------------------------------------------
    */

    if (
      selectedTargetType.value ===
      'menu_item'
    ) {

      const variantOptions =
        availableVariantOptions.value


      if (
        variantOptions.length > 0
        &&
        !selectedVariantId.value
      ) {

        return false
      }


      if (
        selectedVariantId.value
      ) {

        const variant =
          variantOptions.find(
            (
              item,
            ) => {

              return (
                Number(
                  item?.id,
                )
                ===
                Number(
                  selectedVariantId.value,
                )
              )
            },
          )


        if (
          !variant
        ) {

          return false
        }
      }
    }


    const usedIds =
      new Set()


    for (
      const ingredient
      of
      ingredients.value
    ) {

      const materialId =
        Number(
          ingredient.raw_material_id,
        )


      const quantity =
        Number(
          ingredient.quantity,
        )


      if (
        !Number.isInteger(
          materialId,
        )

        ||

        materialId <= 0

        ||

        !Number.isFinite(
          quantity,
        )

        ||

        quantity <= 0

        ||

        quantity >
        9999999999.9999

        ||

        usedIds.has(
          materialId,
        )
      ) {

        return false
      }


      usedIds.add(
        materialId,
      )
    }


    return true
  })


/*
|--------------------------------------------------------------------------
| Mapping Helpers
|--------------------------------------------------------------------------
*/

function mappingKey(
  mapping,
) {

  return recipeMappingIdentity(
    mapping,
  )
}


function mappingHasIngredients(
  mapping,
) {

  return (
    Array.isArray(
      mapping?.ingredients,
    )

    &&

    mapping.ingredients.length >
    0
  )
}


function targetOptionLabel(
  option,
) {

  const prefix =
    option?.target_type ===
    'add_on'

      ? 'Add-on'

      : 'Menu Item'


  return (
    `${prefix} — ${
      option?.target_name
      ||
      ''
    }`
  )
}


function targetOptionDisabled(
  option,
) {

  if (
    option?.target_type ===
    'add_on'
  ) {

    if (
      formMode.value ===
      'edit'
      &&
      selectedTargetType.value ===
      'add_on'
      &&
      Number(
        selectedTargetKey.value,
      )
      ===
      Number(
        option.target_id,
      )
    ) {

      return false
    }


    return addOnHasRecipe(
      option.target_id,
    )
  }


  return !menuItemCanBeSelected(
    option,
  )
}


function targetFallbackName(
  mapping,
) {

  if (
    mapping?.target_type ===
    'add_on'
  ) {

    return (
      `Add-on #${
        mapping?.target_id
        ??
        ''
      }`
    )
  }


  return (
    `Menu Item #${
      mapping?.target_id
      ??
      ''
    }`
  )
}


/*
|--------------------------------------------------------------------------
| Target Type Change
|--------------------------------------------------------------------------
*/

function handleTargetTypeChange() {

  selectedTargetKey.value =
    ''

  selectedVariantId.value =
    ''

  targetSearchQuery.value =
    ''

  variants.value =
    variants.value

  clearFieldError(
    'target_type',
  )

  clearFieldError(
    'target_id',
  )

  clearFieldError(
    'variant_id',
  )

  successMessage.value =
    ''
}


/*
|--------------------------------------------------------------------------
| Select Menu Item Target
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Select Menu Item Target
|--------------------------------------------------------------------------
*/

function selectMenuItemTarget(
  option,
) {

  const menuItemId =
    Number(
      option?.id,
    )


  if (
    !Number.isInteger(
      menuItemId,
    )
    ||
    menuItemId <= 0
  ) {
    return
  }


  if (
    !menuItemCanBeSelected(
      option,
    )
  ) {
    return
  }


  selectedTargetType.value =
    'menu_item'


  selectedTargetKey.value =
    String(
      menuItemId,
    )


  selectedVariantId.value =
    ''


  targetSearchQuery.value =
    ''


  /*
  |----------------------------------------------------------------------
  | Clear field errors
  |----------------------------------------------------------------------
  */

  clearFieldError(
    'target_type',
  )

  clearFieldError(
    'target_id',
  )

  clearFieldError(
    'variant_id',
  )


  /*
  |----------------------------------------------------------------------
  | Ensure local variant collection is also synchronized
  |----------------------------------------------------------------------
  */

  variants.value =
    getMenuItemVariants(
      menuItemId,
    )


  successMessage.value =
    ''
}


/*
|--------------------------------------------------------------------------
| Select Add-on Target
|--------------------------------------------------------------------------
*/

function selectAddOnTarget(
  option,
) {

  if (
    addOnHasRecipe(
      option?.id,
    )
  ) {

    return
  }


  const targetId =
    Number(
      option?.id,
    )


  if (
    !Number.isInteger(
      targetId,
    )
    ||
    targetId <= 0
  ) {

    return
  }


  selectedTargetType.value =
    'add_on'


  selectedTargetKey.value =
    String(
      targetId,
    )


  selectedVariantId.value =
    ''

  variants.value =
    []


  targetSearchQuery.value =
    ''


  clearFieldError(
    'target_type',
  )

  clearFieldError(
    'target_id',
  )

  clearFieldError(
    'variant_id',
  )


  serverErrors.value =
    {}

  successMessage.value =
    ''
}


/*
|--------------------------------------------------------------------------
| Variant Change
|--------------------------------------------------------------------------
*/

function handleVariantChange() {

  clearFieldError(
    'variant_id',
  )


  successMessage.value =
    ''


  if (
    selectedTargetType.value !==
    'menu_item'
  ) {

    selectedVariantId.value =
      ''
  }
}


/*
|--------------------------------------------------------------------------
| Variant Helpers
|--------------------------------------------------------------------------
*/

function rebuildVariantsCollection() {

  const collected = []


  for (
    const menuItem
    of
    menuItems.value
  ) {

    if (
      !Array.isArray(
        menuItem?.variants,
      )
    ) {

      continue
    }


    for (
      const variant
      of
      menuItem.variants
    ) {

      const id =
        Number(
          variant?.id,
        )


      const menuItemId =
        Number(
          variant?.menu_item_id
          ??
          menuItem?.id,
        )


      if (
        !Number.isInteger(
          id,
        )
        ||
        id <= 0
        ||
        !Number.isInteger(
          menuItemId,
        )
        ||
        menuItemId <= 0
      ) {

        continue
      }


      collected.push({

        ...variant,

        id,

        menu_item_id:
          menuItemId,

      })
    }
  }


  const unique =
    new Map()


  for (
    const variant
    of
    collected
  ) {

    unique.set(
      Number(
        variant.id,
      ),
      variant,
    )
  }


  variants.value =
    [
      ...unique.values(),
    ]
}


/*
|--------------------------------------------------------------------------
| Get Menu Item Variants
|--------------------------------------------------------------------------
*/

function getMenuItemVariants(
  menuItemId,
) {

  const normalizedMenuItemId =
    Number(
      menuItemId,
    )


  if (
    !Number.isInteger(
      normalizedMenuItemId,
    )
    ||
    normalizedMenuItemId <= 0
  ) {
    return []
  }


  const item =
    menuItems.value.find(
      (
        menuItem,
      ) => {

        return (
          Number(
            menuItem?.id,
          )
          ===
          normalizedMenuItemId
        )
      },
    )


  if (
    !item
  ) {
    return []
  }


  let itemVariants = []


  if (
    Array.isArray(
      item?.variants,
    )
  ) {
    itemVariants =
      item.variants
  }


  /*
  |----------------------------------------------------------------------
  | Normalize variant objects
  |----------------------------------------------------------------------
  */

  return itemVariants
    .filter(
      (
        variant,
      ) => {

        return (
          variant
          &&
          Number(
            variant?.id,
          )
          > 0
        )
      },
    )
    .map(
      (
        variant,
      ) => {

        return {

          ...variant,

          id:
            Number(
              variant.id,
            ),

          menu_item_id:
            Number(
              variant?.menu_item_id
              ??
              normalizedMenuItemId,
            ),

          variant_name:
            String(
              variant?.variant_name
              ??
              variant?.name
              ??
              `Variant #${variant.id}`,
            ).trim(),

        }
      },
    )
    .sort(
      (
        a,
        b,
      ) => {

        return String(
          a?.variant_name
          ??
          '',
        )
          .localeCompare(
            String(
              b?.variant_name
              ??
              '',
            ),
          )
      },
    )
}


function hasDirectMenuItemRecipe(
  menuItemId,
) {

  return recipeMappings.value.some(
    (
      mapping,
    ) => {

      return (

        mapping?.target_type ===
        'menu_item'

        &&

        Number(
          mapping?.target_id,
        )
        ===
        Number(
          menuItemId,
        )

        &&

        Number(
          mapping?.variant_id
          ||
          0,
        )
        ===
        0

      )
    },
  )
}


function variantHasRecipe(
  menuItemId,
  variantId,
) {

  const targetKey =
    `menu_item:${Number(menuItemId)}:${Number(variantId)}`


  return mappedRecipeKeys.value
    .has(
      targetKey,
    )
}


function menuItemHasNoVariantRecipe(
  menuItemId,
) {

  return recipeMappings.value.some(
    (mapping) => {

      return (
        mapping?.target_type ===
          'menu_item'
        &&
        Number(
          mapping?.target_id,
        )
        ===
        Number(
          menuItemId,
        )
        &&
        !Number(
          mapping?.variant_id
          ||
          0,
        )
      )
    },
  )
}


function menuItemCanBeSelected(
  option,
) {

  const menuItemId =
    Number(
      option?.id,
    )


  const hasDirectRecipe =
    menuItemHasNoVariantRecipe(
      menuItemId,
    )


  const itemVariants =
    Array.isArray(
      option?.variants,
    )
      ? option.variants
      : []


  /*
  |--------------------------------------------------------------------------
  | If direct recipe exists and there are no variants,
  | target cannot be selected.
  |--------------------------------------------------------------------------
  */

  if (
    hasDirectRecipe
    &&
    itemVariants.length === 0
  ) {
    return false
  }


  /*
  |--------------------------------------------------------------------------
  | Menu item with variants can still be selected,
  | because another variant may be available.
  |--------------------------------------------------------------------------
  */

  return true
}


function addOnHasRecipe(
  addOnId,
) {

  return recipeMappings.value.some(
    (mapping) => {

      return (
        mapping?.target_type ===
          'add_on'
        &&
        Number(
          mapping?.target_id,
        )
        ===
        Number(
          addOnId,
        )
      )
    },
  )
}


function variantOptionDisabled(
  variant,
) {

  if (
    !selectedTarget.value
    ||
    selectedTargetType.value !==
      'menu_item'
  ) {
    return true
  }


  const variantId =
    Number(
      variant?.id,
    )


  const menuItemId =
    Number(
      selectedTarget.value
        ?.target_id,
    )


  return recipeMappings.value.some(
    (mapping) => {

      return (
        mapping?.target_type ===
          'menu_item'
        &&
        Number(
          mapping?.target_id,
        )
        ===
        menuItemId
        &&
        Number(
          mapping?.variant_id
          ||
          0,
        )
        ===
        variantId
      )
    },
  )
}


/*
|--------------------------------------------------------------------------
| Image Helpers
|--------------------------------------------------------------------------
*/

function resolveImageUrl(
  value,
) {
  const raw = String(value ?? '').trim()

  if (!raw) {
    return ''
  }

  if (
    raw.startsWith('http://')
    ||
    raw.startsWith('https://')
    ||
    raw.startsWith('data:')
    ||
    raw.startsWith('blob:')
  ) {
    return raw
  }

  if (raw.startsWith('//')) {
    return `${window.location.protocol}${raw}`
  }

  if (raw.startsWith('/')) {
    return raw
  }

  return `/${raw}`
}

function getMenuItemImage(
  item,
) {
  return resolveImageUrl(
    item?.image_url
    ??
    item?.image
    ??
    item?.image_path
    ??
    item?.photo_url
    ??
    item?.thumbnail_url
    ??
    '',
  )
}

function getMappingImage(
  mapping,
) {
  return resolveImageUrl(
    mapping?.image_url
    ??
    mapping?.menu_item?.image_url
    ??
    '',
  )
}

function handleImageError(
  event,
) {
  const image = event?.target

  if (!image) {
    return
  }

  image.style.display = 'none'

  const parent = image.parentElement

  if (!parent) {
    return
  }

  parent.classList.add(
    'recipe-mapping-image-failed',
  )
}


/*
|--------------------------------------------------------------------------
| Menu / Add-on Names
|--------------------------------------------------------------------------
*/

function menuItemName(
  item,
) {

  return String(

    item?.menu_name

    ??

    item?.item_name

    ??

    item?.name

    ??

    item?.title

    ??

    `Menu Item #${
      item?.id
      ??
      ''
    }`

  ).trim()
}


function addOnName(
  addOn,
) {

  return String(

    addOn?.add_on_name

    ??

    addOn?.addon_name

    ??

    addOn?.name

    ??

    `Add-on #${
      addOn?.id
      ??
      ''
    }`

  ).trim()
}


/*
|--------------------------------------------------------------------------
| Ingredient Factory
|--------------------------------------------------------------------------
*/

function createIngredient(
  source = {},
) {

  rowSequence += 1


  const rawMaterialId =
    source?.raw_material_id
    ??
    source?.raw_material?.id
    ??
    ''


  const material =
    findRawMaterial(
      rawMaterialId,
    )


  return {

    _key:
      `recipe-row-${rowSequence}`,

    id:
      source?.id
      ??
      null,

    raw_material_id:
      rawMaterialId
        ? String(
            rawMaterialId,
          )
        : '',

    quantity:
      source?.quantity !==
      undefined

      &&
      source?.quantity !==
      null

        ? formatEditableQuantity(
            source.quantity,
          )

        : '',

    unit:
      source?.unit
      ??
      source?.raw_material?.base_unit
      ??
      material?.base_unit
      ??
      '',

    notes:
      source?.notes
      ??
      '',

    restaurant_stock:
      source?.restaurant_stock
      ??
      findRestaurantStock(
        rawMaterialId,
      )
      ??
      null,

  }
}


/*
|--------------------------------------------------------------------------
| Raw Material Helpers
|--------------------------------------------------------------------------
*/

function rawMaterialLabel(
  material,
) {

  const name =
    String(

      material?.material_name

      ??

      material?.name

      ??

      `Material #${
        material?.id
        ??
        ''
      }`

    ).trim()


  const category =
    String(

      material?.category

      ??

      material?.category_name

      ??

      ''

    ).trim()


  const unit =
    String(

      material?.base_unit

      ??

      material?.unit

      ??

      ''

    ).trim()


  return [

    name,

    category
      ? `— ${category}`
      : '',

    unit
      ? `(${unit})`
      : '',

  ]
    .filter(Boolean)
    .join(' ')
}


function findRawMaterial(
  materialId,
) {

  return rawMaterials.value.find(
    (
      material,
    ) => {

      return (
        String(
          material?.id,
        )
        ===
        String(
          materialId,
        )
      )
    },
  )
  ||
  null
}


function findRestaurantStock(
  materialId,
) {

  if (
    !materialId
  ) {

    return null
  }


  return restaurantStocks.value.find(
    (
      stock,
    ) => {

      return (

        String(

          stock?.raw_material_id

          ??

          stock?.raw_material?.id

          ??

          ''

        )

        ===

        String(
          materialId,
        )

      )
    },
  )
  ||
  null
}


function ingredientRestaurantStock(
  ingredient,
) {

  return (

    ingredient?.restaurant_stock

    ??

    findRestaurantStock(
      ingredient?.raw_material_id,
    )

    ??

    null
  )
}


function ingredientName(
  ingredient,
) {

  return String(

    ingredient?.raw_material
      ?.material_name

    ??

    ingredient?.material_name

    ??

    findRawMaterial(
      ingredient?.raw_material_id,
    )
      ?.material_name

    ??

    `Material #${
      ingredient?.raw_material_id
      ??
      ''
    }`

  ).trim()
}


function ingredientQuantityLabel(
  ingredient,
) {

  if (
    ingredient?.quantity_formatted
  ) {

    return String(
      ingredient.quantity_formatted,
    )
  }


  const quantity =
    formatDisplayQuantity(
      ingredient?.quantity,
    )


  const unit =
    String(

      ingredient?.unit

      ??

      ingredient?.raw_material
        ?.base_unit

      ??

      ''

    ).trim()


  return `${
    quantity
  } ${
    unit
  }`.trim()
}


/*
|--------------------------------------------------------------------------
| Duplicate Ingredient Protection
|--------------------------------------------------------------------------
*/

function materialUsedByOtherRow(
  materialId,
  currentIndex,
) {

  return ingredients.value.some(
    (
      ingredient,
      index,
    ) => {

      if (
        index ===
        currentIndex
      ) {

        return false
      }


      return (

        String(
          ingredient?.raw_material_id
          ||
          '',
        )

        ===

        String(
          materialId,
        )

      )
    },
  )
}


/*
|--------------------------------------------------------------------------
| Raw Material Change
|--------------------------------------------------------------------------
*/

function handleRawMaterialChange(
  ingredient,
  index,
) {

  clearRowError(
    index,
    'raw_material_id',
  )


  const material =
    findRawMaterial(
      ingredient?.raw_material_id,
    )


  ingredient.unit =
    material?.base_unit
    ??
    material?.unit
    ??
    ''


  ingredient.restaurant_stock =
    findRestaurantStock(
      ingredient?.raw_material_id,
    )
}


/*
|--------------------------------------------------------------------------
| Decimal Input
|--------------------------------------------------------------------------
*/

function sanitizeDecimalInput(
  value,
) {

  let normalized =
    String(
      value
      ??
      '',
    )
      .replace(
        /,/g,
        '.',
      )
      .replace(
        /[^0-9.]/g,
        '',
      )


  const firstDot =
    normalized.indexOf(
      '.',
    )


  if (
    firstDot !==
    -1
  ) {

    normalized =
      normalized.slice(
        0,
        firstDot + 1,
      )
      +
      normalized
        .slice(
          firstDot + 1,
        )
        .replace(
          /\./g,
          '',
        )
  }


  const [
    integerPartRaw,
    decimalPartRaw,
  ] =
    normalized.split(
      '.',
    )


  const integerPart =
    String(
      integerPartRaw
      ||
      '',
    )
      .slice(
        0,
        10,
      )


  if (
    decimalPartRaw ===
    undefined
  ) {

    return integerPart
  }


  return `${
    integerPart
  }.${
    String(
      decimalPartRaw,
    )
      .slice(
        0,
        4,
      )
  }`
}


function handleQuantityInput(
  ingredient,
  event,
) {

  const value =
    sanitizeDecimalInput(
      event?.target?.value,
    )


  ingredient.quantity =
    value


  if (
    event?.target
  ) {

    event.target.value =
      value
  }


  const index =
    ingredients.value
      .indexOf(
        ingredient,
      )


  if (
    index >=
    0
  ) {

    clearRowError(
      index,
      'quantity',
    )
  }
}


function handleQuantityPaste(
  ingredient,
  event,
) {

  event.preventDefault()


  const value =
    sanitizeDecimalInput(

      event
        ?.clipboardData
        ?.getData(
          'text',
        )
      ??
      '',
    )


  ingredient.quantity =
    value


  if (
    event?.target
  ) {

    event.target.value =
      value
  }


  const index =
    ingredients.value
      .indexOf(
        ingredient,
      )


  if (
    index >=
    0
  ) {

    clearRowError(
      index,
      'quantity',
    )
  }
}


function formatEditableQuantity(
  value,
) {

  const number =
    Number(
      value,
    )


  if (
    !Number.isFinite(
      number,
    )
  ) {

    return ''
  }


  return number
    .toFixed(
      4,
    )
    .replace(
      /0+$/,
      '',
    )
    .replace(
      /\.$/,
      '',
    )
}


function normalizeQuantityForSnapshot(
  value,
) {

  const number =
    Number(
      value,
    )


  return Number.isFinite(
    number,
  )

    ? number.toFixed(
        4,
      )

    : ''
}


function formatDisplayQuantity(
  value,
) {

  const number =
    Number(
      value,
    )


  if (
    !Number.isFinite(
      number,
    )
  ) {

    return '0'
  }


  return number
    .toFixed(
      4,
    )
    .replace(
      /0+$/,
      '',
    )
    .replace(
      /\.$/,
      '',
    )
}


/*
|--------------------------------------------------------------------------
| Restaurant Stock
|--------------------------------------------------------------------------
*/

function ingredientHasQuantity(
  ingredient,
) {

  const quantity =
    Number(
      ingredient?.quantity,
    )


  return (
    Number.isFinite(
      quantity,
    )
    &&
    quantity > 0
  )
}


function stockQuantity(
  ingredient,
) {

  const quantity =
    Number(

      ingredientRestaurantStock(
        ingredient,
      )?.quantity

      ??

      0

    )


  return Number.isFinite(
    quantity,
  )
    ? quantity
    : 0
}


function stockQuantityLabel(
  ingredient,
) {

  const quantity =
    formatDisplayQuantity(
      stockQuantity(
        ingredient,
      ),
    )


  const unit =
    String(
      ingredient?.unit
      ||
      '',
    )
      .trim()


  return `${
    quantity
  } ${
    unit
  }`.trim()
}


function sufficientForOne(
  ingredient,
) {

  const required =
    Number(
      ingredient?.quantity,
    )


  if (
    !Number.isFinite(
      required,
    )
    ||
    required <= 0
  ) {

    return false
  }


  return (
    stockQuantity(
      ingredient,
    )
    >=
    required
  )
}


function stockStatus(
  ingredient,
) {

  const stock =
    ingredientRestaurantStock(
      ingredient,
    )


  return String(

    stock?.status

    ??

    (
      stockQuantity(
        ingredient,
      ) > 0

        ? 'available'

        : 'out_of_stock'
    )

  )
    .trim()
    .toLowerCase()
}


function stockStatusLabel(
  ingredient,
) {

  const status =
    stockStatus(
      ingredient,
    )


  if (
    status ===
    'limited'
  ) {

    return 'Limited'
  }


  if (
    status ===
    'out_of_stock'
  ) {

    return 'Out of Stock'
  }


  return 'Available'
}


function stockBadgeClass(
  ingredient,
) {

  const status =
    stockStatus(
      ingredient,
    )


  if (
    status ===
    'limited'
  ) {

    return 'text-bg-warning'
  }


  if (
    status ===
    'out_of_stock'
  ) {

    return 'text-bg-danger'
  }


  return 'text-bg-success'
}


/*
|--------------------------------------------------------------------------
| Ingredient Actions
|--------------------------------------------------------------------------
*/

function addIngredient() {

  if (
    !canManageInventory.value
    ||
    !canAddIngredient.value
    ||
    saving.value
  ) {

    return
  }


  errorMessage.value =
    ''

  successMessage.value =
    ''


  ingredients.value.push(
    createIngredient(),
  )
}


function removeIngredient(
  index,
) {

  if (
    !canManageInventory.value
    ||
    saving.value
  ) {

    return
  }


  ingredients.value.splice(
    index,
    1,
  )


  serverErrors.value =
    {}

  successMessage.value =
    ''
}


/*
|--------------------------------------------------------------------------
| Validation Errors
|--------------------------------------------------------------------------
*/

function fieldError(
  field,
) {

  const errors =
    serverErrors.value?.[
      field
    ]


  if (
    Array.isArray(
      errors,
    )
  ) {

    return (
      errors[0]
      ||
      ''
    )
  }


  return errors
    ? String(
        errors,
      )
    : ''
}


function clearFieldError(
  field,
) {

  if (
    !Object.prototype
      .hasOwnProperty
      .call(
        serverErrors.value,
        field,
      )
  ) {

    return
  }


  const nextErrors = {
    ...serverErrors.value,
  }


  delete nextErrors[
    field
  ]


  serverErrors.value =
    nextErrors
}


function rowError(
  index,
  field,
) {

  return fieldError(
    `ingredients.${index}.${field}`,
  )
}


function clearRowError(
  index,
  field,
) {

  clearFieldError(
    `ingredients.${index}.${field}`,
  )
}


/*
|--------------------------------------------------------------------------
| API Collection Normalizer
|--------------------------------------------------------------------------
*/

function normalizeCollectionResponse(
  response,
) {

  const body =
    response?.data


  if (
    Array.isArray(
      body,
    )
  ) {

    return {

      data:
        body,

      meta:
        null,

    }
  }


  if (
    Array.isArray(
      body?.data,
    )
  ) {

    return {

      data:
        body.data,

      meta:
        body.meta
        ??
        body.pagination
        ??
        null,

    }
  }


  if (
    Array.isArray(
      body?.data?.data,
    )
  ) {

    return {

      data:
        body.data.data,

      meta:
        body.data.meta
        ??
        body.meta
        ??
        null,

    }
  }


  return {

    data:
      [],

    meta:
      null,

  }
}


function resolveLastPage(
  meta,
) {

  const lastPage =
    Number(

      meta?.last_page

      ??

      meta?.lastPage

      ??

      1

    )


  return Number.isFinite(
    lastPage,
  )
    ? Math.max(
        1,
        lastPage,
      )
    : 1
}


/*
|--------------------------------------------------------------------------
| Load Menu Items
|--------------------------------------------------------------------------
*/

async function loadAllMenuItems() {

  const collected = []

  let page = 1

  let lastPage = 1


  do {

    const response =
      await api.get(

        '/menu-management/menu-items',

        {
          params: {

            page,

            per_page:
              100,

          },
        },

      )


    const normalized =
      normalizeCollectionResponse(
        response,
      )


    collected.push(
      ...normalized.data,
    )


    lastPage =
      resolveLastPage(
        normalized.meta,
      )


    page += 1


  } while (
    page <= lastPage
    &&
    page <= 100
  )


  const unique =
    new Map()


  for (
    const item
    of
    collected
  ) {

    const id =
      Number(
        item?.id,
      )


    if (
      Number.isInteger(
        id,
      )
      &&
      id > 0
    ) {

      unique.set(
        id,
        item,
      )
    }
  }


  menuItems.value =
    [...unique.values()]
      .sort(
        (
          a,
          b,
        ) => {

          return menuItemName(
            a,
          )
            .localeCompare(
              menuItemName(
                b,
              ),
            )
        },
      )


  rebuildVariantsCollection()
}


/*
|--------------------------------------------------------------------------
| Load Add-ons
|--------------------------------------------------------------------------
*/

async function loadAllAddOns() {

  const collected = []

  let page = 1

  let lastPage = 1


  do {

    const response =
      await api.get(

        '/menu-management/add-ons',

        {
          params: {

            page,

            per_page:
              100,

          },
        },

      )


    const normalized =
      normalizeCollectionResponse(
        response,
      )


    collected.push(
      ...normalized.data,
    )


    lastPage =
      resolveLastPage(
        normalized.meta,
      )


    page += 1


  } while (
    page <= lastPage
    &&
    page <= 100
  )


  const unique =
    new Map()


  for (
    const addOn
    of
    collected
  ) {

    const id =
      Number(
        addOn?.id,
      )


    if (
      Number.isInteger(
        id,
      )
      &&
      id > 0
    ) {

      unique.set(
        id,
        addOn,
      )
    }
  }


  addOns.value =
    [...unique.values()]
      .sort(
        (
          a,
          b,
        ) => {

          return addOnName(
            a,
          )
            .localeCompare(
              addOnName(
                b,
              ),
            )
        },
      )
}

/*
|--------------------------------------------------------------------------
| Load Menu Item Variants
|--------------------------------------------------------------------------
*/

async function loadAllMenuVariants() {
  /*
  |--------------------------------------------------------------------------
  | Prefer variants already included in menu-item payloads.
  |--------------------------------------------------------------------------
  */

  rebuildVariantsCollection()


  /*
  |--------------------------------------------------------------------------
  | Optional fallback endpoint.
  |--------------------------------------------------------------------------
  | If the project exposes a dedicated endpoint, merge its data. If it does
  | not, keep the variants already loaded from menu items.
  |--------------------------------------------------------------------------
  */

  try {
    const collected = []

    let page = 1

    let lastPage = 1


    do {
      const response =
        await api.get(
          '/menu-management/menu-variants',
          {
            params: {
              page,
              per_page: 100,
            },
          },
        )


      const normalized =
        normalizeCollectionResponse(
          response,
        )


      collected.push(
        ...normalized.data,
      )


      lastPage =
        resolveLastPage(
          normalized.meta,
        )


      page += 1

    } while (
      page <= lastPage
      &&
      page <= 100
    )


    const unique =
      new Map()


    for (
      const variant
      of collected
    ) {
      const id =
        Number(
          variant?.id,
        )

      const menuItemId =
        Number(
          variant?.menu_item_id
          ??
          variant?.menuItem?.id
          ??
          0,
        )

      if (
        Number.isInteger(id)
        &&
        id > 0
        &&
        Number.isInteger(menuItemId)
        &&
        menuItemId > 0
      ) {
        unique.set(
          id,
          {
            ...variant,
            id,
            menu_item_id: menuItemId,
          },
        )
      }
    }


    if (unique.size > 0) {
      variants.value = [
        ...new Map(
          [
            ...variants.value,
            ...unique.values(),
          ].map((variant) => [
            Number(variant.id),
            variant,
          ]),
        ).values(),
      ]
    }

  } catch (error) {
    /*
    |--------------------------------------------------------------------------
    | Dedicated endpoint is optional.
    |--------------------------------------------------------------------------
    */

    rebuildVariantsCollection()
  }
}

/*
|--------------------------------------------------------------------------
| Load Raw Materials
|--------------------------------------------------------------------------
*/

async function loadAllRawMaterials() {

  const collected = []

  let page = 1

  let lastPage = 1


  do {

    const result =
      await inventoryService
        .getRawMaterials({

          is_active:
            1,

          page,

          per_page:
            100,

          sort_by:
            'material_name',

          sort_direction:
            'asc',

        })


    collected.push(

      ...(

        Array.isArray(
          result?.data,
        )

          ? result.data

          : []

      ),

    )


    lastPage =
      resolveLastPage(
        result?.meta,
      )


    page += 1


  } while (
    page <= lastPage
    &&
    page <= 100
  )


  const unique =
    new Map()


  for (
    const material
    of
    collected
  ) {

    const id =
      Number(
        material?.id,
      )


    if (
      Number.isInteger(
        id,
      )
      &&
      id > 0
    ) {

      unique.set(
        id,
        material,
      )
    }
  }


  rawMaterials.value =
    [...unique.values()]
      .sort(
        (
          a,
          b,
        ) => {

          return rawMaterialLabel(
            a,
          )
            .localeCompare(
              rawMaterialLabel(
                b,
              ),
            )
        },
      )
}


/*
|--------------------------------------------------------------------------
| Load Restaurant Stocks
|--------------------------------------------------------------------------
*/

async function loadAllRestaurantStocks() {

  const collected = []

  let page = 1

  let lastPage = 1


  do {

    const result =
      await inventoryService
        .getRestaurantStocks({

          page,

          per_page:
            100,

        })


    collected.push(

      ...(

        Array.isArray(
          result?.data,
        )

          ? result.data

          : []

      ),

    )


    lastPage =
      resolveLastPage(
        result?.meta,
      )


    page += 1


  } while (
    page <= lastPage
    &&
    page <= 100
  )


  const unique =
    new Map()


  for (
    const stock
    of
    collected
  ) {

    const materialId =
      Number(

        stock?.raw_material_id

        ??

        stock?.raw_material?.id

      )


    if (
      Number.isInteger(
        materialId,
      )
      &&
      materialId > 0
    ) {

      unique.set(
        materialId,
        stock,
      )
    }
  }


  restaurantStocks.value =
    [...unique.values()]
}


/*
|--------------------------------------------------------------------------
| Load Recipe Mapping List
|--------------------------------------------------------------------------
*/

async function loadRecipeMappings() {

  const result =
    await inventoryService
      .getRecipeMappings()


  if (
    result?.success ===
    false
  ) {

    throw new Error(

      result?.message

      ||

      'Unable to load recipe mappings.',

    )
  }


  recipeMappings.value =
    Array.isArray(
      result?.data,
    )
      ? result.data
      : []
}


/*
|--------------------------------------------------------------------------
| Load Foundation
|--------------------------------------------------------------------------
*/

async function loadFoundation() {

  if (
    !canViewInventory.value
  ) {

    return
  }


  foundationLoading.value =
    true


  errorMessage.value =
    ''


  try {

    const results =
  await Promise.allSettled([
    loadAllMenuItems(),

    loadAllMenuVariants(),

    loadAllAddOns(),

    loadAllRawMaterials(),

    loadAllRestaurantStocks(),

    loadRecipeMappings(),
  ])


    const rejected =
      results.find(
        (
          result,
        ) => {

          return (
            result.status ===
            'rejected'
          )
        },
      )


    if (
      rejected
    ) {

      throw rejected.reason
    }


  } catch (
    error
  ) {

    errorMessage.value =
      inventoryService
        .getInventoryErrorMessage(

          error,

          'Unable to load recipe mapping information.',

        )
  } finally {

    foundationLoading.value =
      false
  }
}


/*
|--------------------------------------------------------------------------
| Form State
|--------------------------------------------------------------------------
*/

function resetFormState() {

  selectedTargetType.value =
    ''

  selectedTargetKey.value =
    ''

  selectedVariantId.value =
    ''

  targetSearchQuery.value =
    ''

  variants.value =
    []

  ingredients.value =
    []

  serverErrors.value =
    {}

  savedSnapshot.value =
    ''
}


function openAddForm() {

  if (
    !canManageInventory.value
    ||
    busy.value
  ) {

    return
  }


  if (
    showForm.value
    &&
    hasChanges.value
    &&
    !window.confirm(
      'Discard the unsaved recipe changes?',
    )
  ) {

    return
  }


  formMode.value =
    'add'


  showForm.value =
    true


  resetFormState()


  selectedTargetType.value =
    ''


  ingredients.value =
    [
      createIngredient(),
    ]


  savedSnapshot.value =
    currentSnapshot.value


  errorMessage.value =
    ''

  successMessage.value =
    ''


  window.requestAnimationFrame(
    scrollFormIntoView,
  )
}


function cancelForm() {

  if (
    saving.value
    ||
    recipeLoading.value
  ) {

    return
  }


  if (
    hasChanges.value
    &&
    !window.confirm(
      'Discard the unsaved recipe changes?',
    )
  ) {

    return
  }


  showForm.value =
    false


  resetFormState()
}


function closeFormAfterSave() {

  showForm.value =
    false


  resetFormState()
}


function scrollFormIntoView() {

  document
    .querySelector(
      '.recipe-mapping-form-card',
    )
    ?.scrollIntoView({

      behavior:
        'smooth',

      block:
        'start',

    })
}


/*
|--------------------------------------------------------------------------
| Edit Mapping
|--------------------------------------------------------------------------
*/

async function editMapping(
  mapping,
) {

  if (
    !canManageInventory.value
    ||
    busy.value
  ) {

    return
  }


  if (
    showForm.value
    &&
    hasChanges.value
    &&
    !window.confirm(
      'Discard the unsaved recipe changes and edit another mapping?',
    )
  ) {

    return
  }


  const targetType =
    String(
      mapping?.target_type
      ||
      '',
    )
      .trim()
      .toLowerCase()


  const targetId =
    Number(
      mapping?.target_id
      ||
      0,
    )


  const variantId =
    targetType ===
    'menu_item'
      ? Number(
          mapping?.variant_id
          ||
          0,
        )
      : 0


  if (
    ![
      'menu_item',
      'add_on',
    ].includes(
      targetType,
    )
    ||
    !Number.isInteger(
      targetId,
    )
    ||
    targetId <= 0
  ) {

    errorMessage.value =
      'The selected recipe mapping has an invalid target.'

    return
  }


  const requestId =
    ++recipeRequestSequence


  formMode.value =
  'edit'


selectedTargetType.value =
  targetType


selectedTargetKey.value =
  String(
    targetId,
  )


selectedVariantId.value =
  targetType ===
  'menu_item'
    ? String(
        mapping?.variant_id
        ||
        '',
      )
    : ''


ingredients.value =
  []


serverErrors.value =
  {}


savedSnapshot.value =
  currentSnapshot.value


showForm.value =
  true


recipeLoading.value =
  true

  errorMessage.value =
    ''

  successMessage.value =
    ''


  window.requestAnimationFrame(
    scrollFormIntoView,
  )


  try {

    variantLoading.value =
      targetType ===
      'menu_item'


    const result =
  await inventoryService
    .getRecipeTarget(
      targetType,
      targetId,
      targetType ===
        'menu_item'
        &&
        mapping?.variant_id
        ? Number(
            mapping.variant_id,
          )
        : null,
    )


    if (
      requestId !==
      recipeRequestSequence
    ) {

      return
    }


    if (
      result?.success ===
      false
    ) {

      throw new Error(

        result?.message

        ||

        'Unable to load recipe mapping.',

      )
    }


    const targetData =
      result?.data


    /*
    |----------------------------------------------------------------------
    | Sync variant from backend
    |----------------------------------------------------------------------
    */

    const backendVariantId =
      Number(
        targetData?.variant_id
        ||
        variantId
        ||
        0,
      )


    if (
      targetType ===
      'menu_item'
      &&
      backendVariantId > 0
    ) {

      selectedVariantId.value =
        String(
          backendVariantId,
        )
    }
    else {

      selectedVariantId.value =
        ''
    }


    /*
    |----------------------------------------------------------------------
    | Ingredient rows
    |----------------------------------------------------------------------
    */

    const rows =
      Array.isArray(
        targetData?.ingredients,
      )
        ? targetData.ingredients
        : []


    ingredients.value =
      rows.map(
        (
          ingredient,
        ) => {

          return createIngredient(
            ingredient,
          )
        },
      )


    /*
    |----------------------------------------------------------------------
    | Keep one empty row only for an empty recipe
    |----------------------------------------------------------------------
    */

    if (
      ingredients.value.length ===
      0
    ) {

      ingredients.value =
        [
          createIngredient(),
        ]
    }


    savedSnapshot.value =
      currentSnapshot.value


  } catch (
    error
  ) {

    if (
      requestId !==
      recipeRequestSequence
    ) {

      return
    }


    errorMessage.value =
      inventoryService
        .getInventoryErrorMessage(

          error,

          'Unable to load recipe mapping.',

        )


    showForm.value =
      false


    resetFormState()


  } finally {

    if (
      requestId ===
      recipeRequestSequence
    ) {

      recipeLoading.value =
        false

      variantLoading.value =
        false
    }
  }
}


/*
|--------------------------------------------------------------------------
| Save Payload
|--------------------------------------------------------------------------
*/

function buildSavePayload() {

  return {

    target_type:
      selectedTarget.value
        ?.target_type,


    target_id:
      Number(
        selectedTarget.value
          ?.target_id,
      ),


    variant_id:
      selectedTargetType.value ===
        'menu_item'
        &&
        selectedVariantId.value !== ''
        ? Number(
            selectedVariantId.value,
          )
        : null,


    ingredients:
      ingredients.value.map(
        (ingredient) => ({

          raw_material_id:
            Number(
              ingredient.raw_material_id,
            ),

          quantity:
            Number(
              ingredient.quantity,
            ),

          notes:
            String(
              ingredient.notes
              ||
              '',
            )
              .trim()
            ||
            null,

        }),
      ),

  }
}


/*
|--------------------------------------------------------------------------
| Save Recipe
|--------------------------------------------------------------------------
*/

async function saveRecipe() {

  if (
    !canManageInventory.value
    ||
    !recipeIsValid.value
    ||
    !hasChanges.value
    ||
    saving.value
  ) {

    return
  }


  saving.value =
    true


  errorMessage.value =
    ''

  successMessage.value =
    ''

  serverErrors.value =
    {}


  try {

    const result =
      await inventoryService
        .saveRecipeTarget(
          buildSavePayload(),
        )


    if (
      result?.success ===
      false
    ) {

      throw new Error(

        result?.message

        ||

        'Unable to save recipe mapping.',

      )
    }


    await loadRecipeMappings()


    successMessage.value =
      result?.message
      ||
      'Recipe mapping saved successfully.'


    closeFormAfterSave()


  } catch (
    error
  ) {

    serverErrors.value =

      error?.response
        ?.data
        ?.errors

      &&

      typeof error
        ?.response
        ?.data
        ?.errors
      ===
      'object'

        ? error
            .response
            .data
            .errors

        : {}


    errorMessage.value =
      inventoryService
        .getInventoryErrorMessage(

          error,

          'Unable to save recipe mapping.',

        )


  } finally {

    saving.value =
      false
  }
}


/*
|--------------------------------------------------------------------------
| Delete Recipe Mapping
|--------------------------------------------------------------------------
*/

async function deleteMapping(
  mapping,
) {

  if (
    !canManageInventory.value
    ||
    busy.value
  ) {

    return
  }


  const targetType =
    String(
      mapping?.target_type
      ||
      '',
    )
      .trim()
      .toLowerCase()


  const targetId =
    Number(
      mapping?.target_id
      ||
      0,
    )


  const variantId =
    targetType ===
    'menu_item'
      ? Number(
          mapping?.variant_id
          ||
          0,
        )
      : 0


  const targetName =
    String(
      mapping?.target_name
      ||
      targetFallbackName(
        mapping,
      ),
    )


  if (
    ![
      'menu_item',
      'add_on',
    ].includes(
      targetType,
    )
    ||
    !Number.isInteger(
      targetId,
    )
    ||
    targetId <= 0
  ) {

    errorMessage.value =
      'The selected recipe mapping has an invalid target.'

    return
  }


  const targetLabel =
    targetType ===
    'add_on'
      ? 'Add-on'
      : 'Menu Item'


  const variantLabel =
    targetType ===
    'menu_item'
    &&
    variantId > 0
      ? ` (${String(
          mapping?.variant_name
          ||
          `Variant #${variantId}`,
        )})`
      : ''


  if (
    !window.confirm(

      `Delete the recipe mapping for "${targetName}"${variantLabel}? The ${targetLabel} itself will not be deleted.`,

    )
  ) {

    return
  }


  const key =
    mappingKey(
      mapping,
    )


  deletingTargetKey.value =
    key


  errorMessage.value =
    ''

  successMessage.value =
    ''


  try {

    const result =
      await inventoryService
        .deleteRecipeTarget(

          targetType,

          targetId,

          targetType ===
          'menu_item'
            && variantId > 0
              ? variantId
              : null,

        )


    if (
      result?.success ===
      false
    ) {

      throw new Error(

        result?.message

        ||

        'Unable to delete recipe mapping.',

      )
    }


    if (
      showForm.value
      &&
      selectedRecipeIdentity.value ===
      key
    ) {

      closeFormAfterSave()
    }


    await loadRecipeMappings()


    successMessage.value =
      result?.message
      ||
      'Recipe mapping deleted successfully.'


  } catch (
    error
  ) {

    errorMessage.value =
      inventoryService
        .getInventoryErrorMessage(

          error,

          'Unable to delete recipe mapping.',

        )


  } finally {

    deletingTargetKey.value =
      ''
  }
}


/*
|--------------------------------------------------------------------------
| Refresh
|--------------------------------------------------------------------------
*/

async function refreshSection() {

  if (
    busy.value
  ) {

    return
  }


  if (
    showForm.value
    &&
    hasChanges.value
    &&
    !window.confirm(
      'Refresh and discard the unsaved recipe changes?',
    )
  ) {

    return
  }


  showForm.value =
    false


  resetFormState()


  successMessage.value =
    ''


  await loadFoundation()
}



/*
|--------------------------------------------------------------------------
| Mount
|--------------------------------------------------------------------------
*/

onMounted(
  loadFoundation,
)
</script>
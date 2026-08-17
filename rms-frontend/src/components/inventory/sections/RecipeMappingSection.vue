<template>
  <section class="recipe-mapping-section">
    <header class="recipe-mapping-header">
      <div>
        <h3>Recipe Mapping</h3>

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


    <!-- Permission -->

    <div
      v-if="!canViewInventory"
      class="alert alert-danger mb-0"
      role="alert"
    >
      You do not have permission to view recipe mappings.
    </div>


    <template v-else>

      <!-- Error -->

      <div
        v-if="errorMessage"
        class="
          alert
          alert-danger
          d-flex
          align-items-start
          gap-2
          mb-0
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


      <!-- Success -->

      <div
        v-if="successMessage"
        class="
          alert
          alert-success
          d-flex
          align-items-start
          gap-2
          mb-0
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


      <!-- Add / Edit Recipe Form -->

      <section
        v-if="showForm"
        class="recipe-mapping-form-card"
        aria-labelledby="recipe-mapping-form-title"
      >
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


        <!-- Recipe Loading -->

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

          <!-- Target -->

          <div class="recipe-mapping-target-block">
            <label
              for="recipe-target"
              class="form-label fw-semibold"
            >
              Item / Add-on

              <span class="text-danger">
                *
              </span>
            </label>

            <select
              id="recipe-target"
              v-model="selectedTargetKey"
              class="form-select"
              :class="{
                'is-invalid':
                  Boolean(
                    fieldError(
                      'target_type',
                    )
                    ||
                    fieldError(
                      'target_id',
                    ),
                  ),
              }"
              :disabled="
                saving
                ||
                formMode === 'edit'
                ||
                targetOptions.length === 0
              "
              @change="handleTargetChange"
            >
              <option value="">
                Select a Menu Item or Add-on
              </option>

              <option
                v-for="option in targetOptions"
                :key="option.key"
                :value="option.key"
                :disabled="
                  targetOptionDisabled(
                    option,
                  )
                "
              >
                {{
                  targetOptionLabel(
                    option,
                  )
                }}
              </option>
            </select>

            <div
              v-if="
                fieldError(
                  'target_type',
                )
                ||
                fieldError(
                  'target_id',
                )
              "
              class="invalid-feedback d-block"
            >
              {{
                fieldError(
                  'target_type',
                )
                ||
                fieldError(
                  'target_id',
                )
              }}
            </div>

            <div class="recipe-mapping-target-help">
              <span
                v-if="formMode === 'edit'"
                class="recipe-mapping-target-lock"
              >
                <i
                  class="bi bi-lock me-1"
                  aria-hidden="true"
                ></i>

                Target identity is locked while editing.
              </span>

              <span v-else>
                Targets that already have a recipe mapping are disabled.
              </span>
            </div>
          </div>


          <!-- Ingredients -->

          <div class="recipe-mapping-form-body">
            <div class="recipe-mapping-form-section-heading">
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


            <!-- Empty Ingredients -->

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


            <!-- Ingredient Rows -->

            <div
              v-else
              class="recipe-mapping-editor-list"
            >
              <article
                v-for="(
                  ingredient,
                  index
                ) in ingredients"
                :key="ingredient._key"
                class="recipe-mapping-ingredient-row"
              >

                <!-- Index -->

                <div class="recipe-mapping-ingredient-index">
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


                <!-- Restaurant Stock -->

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
                      class="recipe-mapping-stock-warning"
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

                <div class="recipe-mapping-ingredient-action">
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


            <!-- Add Ingredient -->

            <div class="recipe-mapping-add-ingredient-bar">
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


          <!-- Form Footer -->

          <footer class="recipe-mapping-form-footer">
            <div class="recipe-mapping-footer-note">
              <i
                class="bi bi-info-circle"
                aria-hidden="true"
              ></i>

              <span>
                Saving changes the recipe definition only.
                Stock is deducted when the kitchen starts preparing an order.
              </span>
            </div>

            <div class="recipe-mapping-form-actions">
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
                  class="spinner-border spinner-border-sm me-2"
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


      <!-- Configured Mapping List -->

      <section class="recipe-mapping-list-card">
        <div class="recipe-mapping-list-header">
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

          <div class="recipe-mapping-search">
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


        <!-- Main Loading -->

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


        <!-- No Mapping -->

        <div
          v-else-if="
            recipeMappings.length === 0
          "
          class="recipe-mapping-list-empty"
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


        <!-- Search Empty -->

        <div
          v-else-if="
            filteredRecipeMappings.length === 0
          "
          class="recipe-mapping-list-empty"
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


        <!-- Mapping Table -->

        <div
          v-else
          class="recipe-mapping-list-table-wrap"
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
                  class="recipe-mapping-list-action-column"
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

                <!-- Target -->

                <td>
                  <div class="recipe-mapping-target-cell">
                    <div class="recipe-mapping-target-icon">
                      <i
                        :class="
                          mapping.target_type === 'add_on'
                            ? 'bi bi-plus-circle'
                            : 'bi bi-cup-hot'
                        "
                        aria-hidden="true"
                      ></i>
                    </div>

                    <div class="recipe-mapping-target-content">
                      <div class="recipe-mapping-target-title">
                        {{
                          mapping.target_name
                          ||
                          targetFallbackName(
                            mapping,
                          )
                        }}
                      </div>

                      <div class="recipe-mapping-target-meta">
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


                <!-- Ingredients -->

                <td>
                  <div class="recipe-mapping-summary-list">
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
                      class="recipe-mapping-summary-chip"
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


                <!-- Actions -->

                <td
                  v-if="canManageInventory"
                  class="recipe-mapping-list-actions"
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
                      class="spinner-border spinner-border-sm"
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

const selectedTargetKey =
  ref('')

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


const mappedTargetKeys =
  computed(() => {

    return new Set(

      recipeMappings.value.map(
        (mapping) => {

          return mappingKey(
            mapping,
          )
        },
      ),

    )
  })


const availableTargetCount =
  computed(() => {

    return targetOptions.value
      .filter(
        (option) => {

          return (
            !mappedTargetKeys.value
              .has(
                option.key,
              )
          )
        },
      )
      .length
  })


const selectedTarget =
  computed(() => {

    return targetOptions.value
      .find(
        (option) => {

          return (
            option.key
            ===
            selectedTargetKey.value
          )
        },
      )
      ||
      null
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
        (mapping) => {

          const ingredientText =
            mappingHasIngredients(
              mapping,
            )

              ? mapping.ingredients
                  .map(
                    (ingredient) => {

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


          return [
            mapping?.target_name,
            typeText,
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

      target:
        selectedTargetKey.value,


      ingredients:
        ingredients.value.map(
          (ingredient) => ({

            raw_material_id:
              String(
                ingredient.raw_material_id
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

          }),
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
      ingredients.value.length === 0
    ) {
      return false
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

        quantity
        >
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

  return `${
    String(
      mapping?.target_type
      ||
      '',
    )
  }:${
    Number(
      mapping?.target_id
      ||
      0,
    )
  }`
}


function mappingHasIngredients(
  mapping,
) {

  return (
    Array.isArray(
      mapping?.ingredients,
    )
    &&
    mapping.ingredients.length > 0
  )
}


function targetOptionLabel(
  option,
) {

  const prefix =
    option?.target_type
    ===
    'add_on'

      ? 'Add-on'

      : 'Menu Item'


  return `${
    prefix
  } — ${
    option?.target_name
    ||
    ''
  }`
}


function targetOptionDisabled(
  option,
) {

  if (
    formMode.value === 'edit'
    &&
    option.key
    ===
    selectedTargetKey.value
  ) {
    return false
  }


  return mappedTargetKeys.value
    .has(
      option.key,
    )
}


function targetFallbackName(
  mapping,
) {

  if (
    mapping?.target_type
    ===
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


function handleTargetChange() {

  clearFieldError(
    'target_type',
  )

  clearFieldError(
    'target_id',
  )

  successMessage.value = ''
}


/*
|--------------------------------------------------------------------------
| Menu / Add-on Name
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

    `Menu Item #${item?.id ?? ''}`

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

    `Add-on #${addOn?.id ?? ''}`

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

    source.raw_material_id

    ??

    source.raw_material?.id

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
      source.id
      ??
      null,


    raw_material_id:

      rawMaterialId

        ? String(
            rawMaterialId,
          )

        : '',


    quantity:

      source.quantity !== undefined

      &&

      source.quantity !== null

        ? formatEditableQuantity(
            source.quantity,
          )

        : '',


    unit:

      source.unit

      ??

      source.raw_material
        ?.base_unit

      ??

      material?.base_unit

      ??

      '',


    notes:
      source.notes
      ??
      '',


    restaurant_stock:

      source.restaurant_stock

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

      `Material #${material?.id ?? ''}`

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
    (material) => {

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
    (stock) => {

      return (

        String(

          stock?.raw_material_id

          ??

          stock?.raw_material?.id

          ??

          '',

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

    ingredient
      ?.restaurant_stock

    ??

    findRestaurantStock(
      ingredient
        ?.raw_material_id,
    )

    ??

    null

  )
}


function ingredientName(
  ingredient,
) {

  return String(

    ingredient
      ?.raw_material
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

      ingredient
        ?.raw_material
        ?.base_unit

      ??

      '',

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
          ingredient
            .raw_material_id
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
      ingredient
        .raw_material_id,
    )


  ingredient.unit =

    material?.base_unit

    ??

    material?.unit

    ??

    ''


  ingredient.restaurant_stock =
    findRestaurantStock(
      ingredient
        .raw_material_id,
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
    firstDot !== -1
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
    decimalPartRaw
    ===
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
    index >= 0
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

      event.clipboardData
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
    index >= 0
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
    .toFixed(4)
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

    ? number.toFixed(4)

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
    .toFixed(4)
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
      )
        ?.quantity

      ??

      0,

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
    ),

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
    status === 'limited'
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
    status === 'limited'
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


  errorMessage.value = ''

  successMessage.value = ''


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


  serverErrors.value = {}

  successMessage.value = ''
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

      1,

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

        stock?.raw_material?.id,

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
| Load Mapping List
|--------------------------------------------------------------------------
*/

async function loadRecipeMappings() {

  const result =
    await inventoryService
      .getRecipeMappings()


  if (
    result?.success
    ===
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
| Foundation
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

        loadAllAddOns(),

        loadAllRawMaterials(),

        loadAllRestaurantStocks(),

        loadRecipeMappings(),

      ])


    const rejected =
      results.find(
        (result) => {

          return (
            result.status
            ===
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

  selectedTargetKey.value =
    ''

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


  ingredients.value = [
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


  const targetId =
    Number(
      mapping?.target_id
      ||
      0,
    )


  if (
    !targetType
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


  selectedTargetKey.value =
    `${targetType}:${targetId}`


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

    const result =
      await inventoryService
        .getRecipeTarget(
          targetType,
          targetId,
        )


    if (
      requestId
      !==
      recipeRequestSequence
    ) {
      return
    }


    if (
      result?.success
      ===
      false
    ) {

      throw new Error(

        result?.message

        ||

        'Unable to load recipe mapping.',

      )
    }


    const rows =

      Array.isArray(
        result?.data?.ingredients,
      )

        ? result.data.ingredients

        : []


    ingredients.value =
      rows.map(
        (ingredient) => {

          return createIngredient(
            ingredient,
          )
        },
      )


    savedSnapshot.value =
      currentSnapshot.value


  } catch (
    error
  ) {

    if (
      requestId
      !==
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
      requestId
      ===
      recipeRequestSequence
    ) {

      recipeLoading.value =
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
      result?.success
      ===
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
        .response
        .data
        .errors
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
| Delete Mapping
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


  const targetId =
    Number(
      mapping?.target_id
      ||
      0,
    )


  const targetName =
    String(

      mapping?.target_name

      ||

      targetFallbackName(
        mapping,
      ),

    )


  if (
    !targetType
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


  if (
    !window.confirm(
      `Delete the recipe mapping for "${targetName}"? The ${targetLabel} itself will not be deleted.`,
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
        )


    if (
      result?.success
      ===
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
      selectedTargetKey.value
      ===
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
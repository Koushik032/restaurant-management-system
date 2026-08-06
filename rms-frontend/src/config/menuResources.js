export const MENU_RESOURCES = {
  categories: {
    endpoint:
      "/menu-management/menu-categories",

    statusAction: "status",

    defaultFilters: {
      search: "",
      status: "",
      page: 1,
      per_page: 10,
    },
  },

  menuItems: {
    endpoint:
      "/menu-management/menu-items",

    statusAction: "status",
    featuredAction: "featured",

    defaultFilters: {
      search: "",
      menu_category_id: "",
      item_type: "",
      status: "",
      featured: "",
      page: 1,
      per_page: 10,
    },
  },

  variants: {
    endpoint:
      "/menu-management/menu-variants",

    statusAction: "status",

    defaultFilters: {
      search: "",
      menu_item_id: "",
      status: "",
      page: 1,
      per_page: 10,
    },
  },

  addOns: {
    endpoint:
      "/menu-management/add-ons",

    statusAction: "status",

    defaultFilters: {
      search: "",
      status: "",
      page: 1,
      per_page: 10,
    },
  },
};

export const getMenuResource = (
  resourceName
) => {
  const resource =
    MENU_RESOURCES[resourceName];

  if (!resource) {
    throw new Error(
      `Menu resource "${resourceName}" was not found.`
    );
  }

  return resource;
};
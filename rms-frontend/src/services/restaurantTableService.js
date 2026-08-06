import api from "@/services/api";

export const restaurantTableService = {
  async getTables(params = {}) {
    const response = await api.get(
      "/table-management/tables",
      {
        params,
      }
    );

    return response.data;
  },

  async getTable(tableId) {
    const response = await api.get(
      `/table-management/tables/${tableId}`
    );

    return response.data;
  },

  async getEditOptions(tableId) {
    const response = await api.get(
      `/table-management/tables/${tableId}/edit-options`
    );

    return response.data;
  },
createTable(payload) {
  return api
    .post(
      "/table-management/tables",
      payload
    )
    .then(
      (response) =>
        response.data
    );
},

  async updateTable(
    tableId,
    payload
  ) {
    const response = await api.put(
      `/table-management/tables/${tableId}`,
      payload
    );

    return response.data;
  },

  async deleteTable(tableId) {
    const response = await api.delete(
      `/table-management/tables/${tableId}`
    );

    return response.data;
  },

  async getMergeOptions(tableId) {
    const response = await api.get(
      `/table-management/tables/${tableId}/merge-options`
    );

    return response.data;
  },

  async mergeTables(
    tableId,
    tableIds
  ) {
    const response = await api.post(
      `/table-management/tables/${tableId}/merge`,
      {
        table_ids: tableIds,
      }
    );

    return response.data;
  },

  async splitTableGroup(tableId) {
    const response = await api.post(
      `/table-management/tables/${tableId}/split`
    );

    return response.data;
  },
};

export default restaurantTableService;
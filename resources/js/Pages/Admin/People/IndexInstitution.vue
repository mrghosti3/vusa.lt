<template>
  <IndexPageLayout
    title="Institucijos"
    model-name="institutions"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="institutions"
    :icon="Icons.INSTITUTION"
    @search:complete="handleCompletedSearch"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import type { DataTableColumns } from "naive-ui";

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

defineProps<{
  institutions: PaginatedModels<App.Entities.Institution[]>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: true,
};

const filterOptionValues = ref([]);

const handleCompletedSearch = (search: any) => {
  console.log("search", search);
  filterOptionValues.value = search["padalinys.id"];
};

const columns: DataTableColumns<App.Entities.Institution> = [
  {
    title() {
      return $t("forms.fields.title");
    },
    key: "name",
    minWidth: 200,
  },
  {
    key: "alias",
    width: 55,
    render(row) {
      return (
        <PreviewModelButton
          publicRoute="contacts.alias"
          routeProps={{ alias: row.alias, lang: "lt", padalinys: "www" }}
        />
      );
    },
  },
  {
    title() {
      return $t("forms.fields.short_name");
    },
    key: "short_name",
  },
  {
    title: "Padalinys",
    key: "padalinys.id",
    filter: true,
    filterOptionValues: [],
    filterOptions: usePage().props.padaliniai.map((padalinys) => {
      return {
        label: padalinys.shortname,
        value: padalinys.id,
      };
    }),
    render(row) {
      return row.padalinys?.shortname;
    },
  },
];
</script>
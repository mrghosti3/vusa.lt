<template>
  <PageContent title="Naujas pasikeitimas" :back-url="route('changelogItems.index')">
    <UpsertModelLayout>
      <ChangelogItemForm remember-key="CreateChangelogItem" :changelog-item="changelogItemTemplate"
        @submit:form="(form) => form.post(route('changelogItems.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { changelogItemTemplate } from "@/Types/formTemplates";
import ChangelogItemForm from "@/Components/AdminForms/ChangelogItemForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
</script>

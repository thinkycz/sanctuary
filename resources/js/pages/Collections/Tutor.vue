<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import CollectionLayout from '@/layouts/CollectionLayout.vue';
import AITutorPanel from '@/components/collection/AITutorPanel.vue';
import type { Collection, TutorMessage } from '@/types';

defineProps<{
    collection: Collection;
    messages: TutorMessage[];
}>();

const { t } = useI18n();
</script>

<template>
    <CollectionLayout :title="t('tabs.tutor')" :collection="collection">
        <div class="flex flex-1 flex-col overflow-hidden">
            <AITutorPanel
                :messages="messages"
                :action="`/collections/${collection.id}/tutor`"
            />
        </div>
    </CollectionLayout>
</template>

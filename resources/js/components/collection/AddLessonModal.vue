<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import ModalOverlay from '@/components/ui/ModalOverlay.vue';
import Select from '@/components/ui/Select.vue';
import { useSharedProps } from '@/composables/useSharedProps';

const props = defineProps<{
    open: boolean;
    collectionId: number;
}>();

const emit = defineEmits<{
    close: [];
}>();

const { t } = useI18n();
const { errors } = useSharedProps();

const form = ref({
    title: '',
    source_text: '',
    difficulty: 'intermediate',
});

const submitting = ref(false);

const difficultyOptions = [
    { value: 'beginner', label: t('lessons.difficulty.beginner') },
    { value: 'intermediate', label: t('lessons.difficulty.intermediate') },
    { value: 'advanced', label: t('lessons.difficulty.advanced') },
];

function submit(): void {
    submitting.value = true;
    router.post(`/collections/${props.collectionId}/lessons`, form.value, {
        onFinish: () => {
            submitting.value = false;
        },
        onSuccess: () => {
            emit('close');
            resetForm();
        },
    });
}

function resetForm(): void {
    form.value = {
        title: '',
        source_text: '',
        difficulty: 'intermediate',
    };
}
</script>

<template>
    <ModalOverlay
        :open="props.open"
        labelled-by="add-lesson-title"
        panel-class="max-w-lg"
        @close="emit('close')"
    >
        <div class="space-y-4">
            <h2
                id="add-lesson-title"
                class="font-heading text-base font-bold text-on-surface"
            >
                {{ t('lessons.new') }}
            </h2>

            <div class="space-y-3">
                <div>
                    <Label for="lesson-title">{{
                        t('lessons.fields.title')
                    }}</Label>
                    <Input
                        id="lesson-title"
                        v-model="form.title"
                        type="text"
                        :placeholder="t('lessons.fields.title_placeholder')"
                    />
                </div>

                <div>
                    <Label for="lesson-source-text">{{
                        t('lessons.fields.source_text')
                    }}</Label>
                    <textarea
                        id="lesson-source-text"
                        v-model="form.source_text"
                        rows="6"
                        :placeholder="
                            t('lessons.fields.source_text_placeholder')
                        "
                        class="w-full rounded-xl border border-outline-glass bg-surface-container-low px-3 py-2 text-xs text-on-surface placeholder:text-on-surface-variant focus:border-primary focus:outline-none"
                    ></textarea>
                    <FieldError :message="errors.source_text" />
                </div>

                <div>
                    <Label for="lesson-difficulty">{{
                        t('lessons.fields.difficulty')
                    }}</Label>
                    <Select
                        id="lesson-difficulty"
                        v-model="form.difficulty"
                        :options="difficultyOptions"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button variant="ghost" @click="emit('close')">
                    {{ t('actions.cancel') }}
                </Button>
                <Button
                    :disabled="submitting || !form.source_text.trim()"
                    @click="submit"
                >
                    {{ t('lessons.generate') }}
                </Button>
            </div>
        </div>
    </ModalOverlay>
</template>

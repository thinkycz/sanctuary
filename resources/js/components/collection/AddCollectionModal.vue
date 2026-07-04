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
}>();

const emit = defineEmits<{
    close: [];
}>();

const { t } = useI18n();
const { errors } = useSharedProps();

const form = ref({
    title: '',
    description: '',
    icon: '📚',
    subject: '',
});

const submitting = ref(false);

const subjectOptions = [
    { value: '', label: '—' },
    { value: 'Mathematics', label: 'Mathematics' },
    { value: 'Computer Science', label: 'Computer Science' },
    { value: 'Physics', label: 'Physics' },
    { value: 'Chemistry', label: 'Chemistry' },
    { value: 'Biology', label: 'Biology' },
    { value: 'History', label: 'History' },
    { value: 'Languages', label: 'Languages' },
    { value: 'Engineering', label: 'Engineering' },
    { value: 'Economics', label: 'Economics' },
    { value: 'Philosophy', label: 'Philosophy' },
];

const iconOptions = [
    '📚',
    '🔬',
    '💻',
    '🧮',
    '🌍',
    '⚡',
    '🎓',
    '🧪',
    '📖',
    '💡',
];

function submit(): void {
    submitting.value = true;
    router.post('/collections', form.value, {
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
        description: '',
        icon: '📚',
        subject: '',
    };
}
</script>

<template>
    <ModalOverlay
        :open="props.open"
        labelled-by="add-collection-title"
        @close="emit('close')"
    >
        <div class="space-y-4">
            <h2
                id="add-collection-title"
                class="font-heading text-base font-bold text-on-surface"
            >
                {{ t('collections.new') }}
            </h2>

            <div class="space-y-3">
                <div>
                    <Label for="collection-title">{{
                        t('collections.fields.title')
                    }}</Label>
                    <Input
                        id="collection-title"
                        v-model="form.title"
                        type="text"
                        :placeholder="t('collections.fields.title_placeholder')"
                    />
                    <FieldError :message="errors.title" />
                </div>

                <div>
                    <Label for="collection-description">{{
                        t('collections.fields.description')
                    }}</Label>
                    <Input
                        id="collection-description"
                        v-model="form.description"
                        type="text"
                        :placeholder="
                            t('collections.fields.description_placeholder')
                        "
                    />
                </div>

                <div>
                    <Label>{{ t('collections.fields.icon') }}</Label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="icon in iconOptions"
                            :key="icon"
                            type="button"
                            class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border text-lg transition"
                            :class="
                                form.icon === icon
                                    ? 'border-primary bg-primary/10'
                                    : 'border-outline-glass hover:bg-surface-container-low'
                            "
                            @click="form.icon = icon"
                        >
                            {{ icon }}
                        </button>
                    </div>
                </div>

                <div>
                    <Label for="collection-subject">{{
                        t('collections.fields.subject')
                    }}</Label>
                    <Select
                        id="collection-subject"
                        v-model="form.subject"
                        :options="subjectOptions"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button variant="ghost" @click="emit('close')">
                    {{ t('actions.cancel') }}
                </Button>
                <Button
                    :disabled="submitting || !form.title.trim()"
                    @click="submit"
                >
                    {{ t('actions.create') }}
                </Button>
            </div>
        </div>
    </ModalOverlay>
</template>

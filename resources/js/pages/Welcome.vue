<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Urgency = 'low' | 'normal' | 'high';
type FormDraft = {
    customer: string | null;
    email: string | null;
    product: string | null;
    urgency: Urgency | null;
    summary: string | null;
    raw_text: string;
};
type DemoMessage = { id: number; title: string; raw_text: string };
type IntakeSummary = {
    id: number;
    customer: string | null;
    email: string | null;
    product: string | null;
    urgency: Urgency;
    summary: string;
    created_at?: string;
};

const props = defineProps<{
    examples: DemoMessage[];
    recentForms: IntakeSummary[];
    llmMode: string;
}>();

const message = ref('');
const draft = ref<FormDraft | null>(null);
const errors = ref<Record<string, string[]>>({});
const recentForms = ref([...props.recentForms]);
const busy = ref(false);
const busyAction = ref<'preview' | 'save' | null>(null);
const savedCurrentDraft = ref(false);
const notice = ref('');
const failure = ref('');

const isReady = computed(() => draft.value !== null);
const wordCount = computed(
    () => message.value.trim().split(/\s+/).filter(Boolean).length,
);

function fieldError(field: string): string | undefined {
    return errors.value[field]?.[0];
}

function clearFieldError(field: string): void {
    delete errors.value[field];
    savedCurrentDraft.value = false;
    notice.value = '';
}

function chooseExample(example: DemoMessage): void {
    message.value = example.raw_text;
    draft.value = null;
    savedCurrentDraft.value = false;
    errors.value = {};
    notice.value = '';
    failure.value = '';
}

async function send(
    url: string,
    payload: Record<string, unknown>,
): Promise<Response> {
    const token = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    )?.content;

    return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify(payload),
    });
}

async function fillForm(): Promise<void> {
    if (!message.value.trim()) {
        failure.value =
            'Paste a support message or choose a sample to get started.';
        return;
    }

    busy.value = true;
    busyAction.value = 'preview';
    savedCurrentDraft.value = false;
    notice.value = '';
    failure.value = '';
    errors.value = {};

    try {
        const response = await send('/intake/preview', {
            raw_text: message.value,
        });
        const result = await response.json();

        if (!response.ok) {
            errors.value = result.errors ?? {};
            failure.value =
                result.message ??
                'The message could not be processed. Please try again.';
            return;
        }

        draft.value = result.form;
        errors.value = result.errors ?? {};
    } catch {
        failure.value =
            'Could not reach Harbor Desk. Check your connection and try again.';
    } finally {
        busy.value = false;
        busyAction.value = null;
    }
}

async function saveForm(): Promise<void> {
    if (!draft.value || savedCurrentDraft.value) return;

    busy.value = true;
    busyAction.value = 'save';
    notice.value = '';
    failure.value = '';

    try {
        const response = await send(
            '/intake-forms',
            draft.value as unknown as Record<string, unknown>,
        );
        const result = await response.json();

        if (!response.ok) {
            errors.value = result.errors ?? {};
            failure.value =
                result.message ?? 'Please correct the highlighted fields.';
            return;
        }

        recentForms.value = [result.intakeForm, ...recentForms.value].slice(
            0,
            4,
        );
        savedCurrentDraft.value = true;
        notice.value = `Intake #${result.intakeForm.id} saved to Harbor Desk.`;
        errors.value = {};
    } catch {
        failure.value = 'The form could not be saved. Please try again.';
    } finally {
        busy.value = false;
        busyAction.value = null;
    }
}
</script>

<template>
    <Head title="AI intake desk">
        <meta
            name="description"
            content="Turn free-form support messages into accurate, reviewable intake forms."
        />
    </Head>

    <div class="min-h-screen bg-[#f5f6f2] text-[#172a24]">
        <header class="border-b border-[#e4e8e0] bg-[#fbfcf9]">
            <div
                class="mx-auto flex max-w-[1320px] items-center justify-between px-5 py-4 sm:px-8 lg:px-12"
            >
                <a
                    href="/"
                    class="flex items-center gap-3"
                    aria-label="Harbor Desk home"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-[#173d32] text-[#d8f1a2] shadow-sm shadow-[#173d32]/15"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            class="size-5"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 17.5V7l8-3 8 3v10.5l-8 3-8-3Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                            <path
                                d="m4.5 7.5 7.5 3 7.5-3M12 10.5v9.5"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>
                    <span>
                        <span
                            class="block text-[14px] font-semibold tracking-[-0.02em]"
                            >Harbor Desk</span
                        >
                        <span
                            class="block text-[11px] font-medium tracking-[0.11em] text-[#78877e] uppercase"
                            >Support operations</span
                        >
                    </span>
                </a>

                <div class="flex items-center gap-3">
                    <span
                        class="hidden rounded-full border border-[#e1e8dc] bg-white px-3 py-1.5 text-[11px] font-medium text-[#52665b] sm:inline-flex sm:items-center sm:gap-2"
                    >
                        <span class="size-1.5 rounded-full bg-[#6a9b55]"></span>
                        {{ llmMode }}
                    </span>
                    <span
                        class="flex size-9 items-center justify-center rounded-full border border-[#e3e8df] bg-white text-xs font-semibold text-[#476057]"
                        >HD</span
                    >
                </div>
            </div>
        </header>

        <main
            class="mx-auto max-w-[1320px] px-5 pt-9 pb-16 sm:px-8 sm:pt-12 lg:px-12 lg:pt-14"
        >
            <section
                class="mb-9 flex flex-col justify-between gap-7 lg:mb-11 lg:flex-row lg:items-end"
            >
                <div class="max-w-[750px]">
                    <div
                        class="mb-4 inline-flex items-center gap-2 rounded-full border border-[#dfe8d7] bg-[#edf4e8] px-3 py-1.5 text-[11px] font-semibold tracking-[0.1em] text-[#557144] uppercase"
                    >
                        <svg
                            viewBox="0 0 16 16"
                            fill="none"
                            class="size-3.5"
                            aria-hidden="true"
                        >
                            <path
                                d="M8 1.7 9.8 6.2l4.5 1.8-4.5 1.8L8 14.3l-1.8-4.5L1.7 8l4.5-1.8L8 1.7Z"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linejoin="round"
                            />
                        </svg>
                        AI INTAKE WORKSPACE
                    </div>
                    <h1
                        class="max-w-[760px] text-[clamp(2.5rem,5.1vw,4.5rem)] leading-[0.99] font-medium tracking-[-0.065em] text-[#18382e]"
                    >
                        Every message has a form
                        <span
                            class="font-serif font-normal text-[#7b916b] italic"
                            >hiding inside.</span
                        >
                    </h1>
                    <p
                        class="mt-5 max-w-[580px] text-[15px] leading-7 text-[#67776e] sm:text-base"
                    >
                        Turn a customer’s note into a clean support intake in
                        seconds. Review every field, fix what’s missing, then
                        save with confidence.
                    </p>
                </div>

                <div
                    class="flex items-center gap-3 self-start rounded-2xl border border-[#e4e9df] bg-white/75 px-4 py-3 lg:self-auto"
                >
                    <div class="flex -space-x-1.5">
                        <span
                            class="flex size-7 items-center justify-center rounded-full border-2 border-white bg-[#e7efdb] text-[10px] font-bold text-[#516d47]"
                            >01</span
                        >
                        <span
                            class="flex size-7 items-center justify-center rounded-full border-2 border-white bg-[#d9e9e3] text-[10px] font-bold text-[#35665b]"
                            >02</span
                        >
                        <span
                            class="flex size-7 items-center justify-center rounded-full border-2 border-white bg-[#f1e8d8] text-[10px] font-bold text-[#967745]"
                            >03</span
                        >
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-[#354d42]">
                            One message. One clear next step.
                        </p>
                        <p class="mt-0.5 text-[11px] text-[#89958c]">
                            Extract · Review · Save
                        </p>
                    </div>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-[24px] border border-[#e1e6dd] bg-white shadow-[0_22px_70px_-45px_rgba(35,59,45,0.3)]"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-[#e9ece6] px-5 py-4 sm:px-7"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-8 items-center justify-center rounded-lg bg-[#eff4ea] text-[#51744b]"
                        >
                            <svg
                                viewBox="0 0 20 20"
                                fill="none"
                                class="size-4"
                                aria-hidden="true"
                            >
                                <path
                                    d="M5 4.5h10M5 8h10M5 11.5h6M4 2.5h12a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-13a1 1 0 0 1 1-1Z"
                                    stroke="currentColor"
                                    stroke-width="1.4"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>
                        <div>
                            <h2
                                class="text-sm font-semibold tracking-[-0.015em] text-[#294238]"
                            >
                                New support intake
                            </h2>
                            <p class="mt-0.5 text-[11px] text-[#8a968e]">
                                Paste a message to create a reviewable draft
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-2 text-[11px] font-medium text-[#8a968e]"
                    >
                        <span class="rounded-md bg-[#f4f6f2] px-2 py-1"
                            >HARBOR DESK</span
                        >
                        <span>·</span>
                        <span
                            >Intake #{{
                                String(recentForms[0]?.id ?? 2048 + 1).padStart(
                                    4,
                                    '0',
                                )
                            }}</span
                        >
                    </div>
                </div>

                <div class="grid lg:grid-cols-[0.92fr_1.08fr]">
                    <div
                        class="border-b border-[#e9ece6] p-5 sm:p-7 lg:border-r lg:border-b-0 lg:p-8"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <label
                                for="source-message"
                                class="text-[11px] font-bold tracking-[0.12em] text-[#688071] uppercase"
                                >01 &nbsp; Customer message</label
                            >
                            <span class="text-[11px] text-[#a0aaa2]"
                                >{{ wordCount }} words</span
                            >
                        </div>
                        <textarea
                            id="source-message"
                            v-model="message"
                            rows="8"
                            maxlength="10000"
                            placeholder="Paste a support message here…"
                            class="min-h-[210px] w-full resize-y rounded-xl border border-[#e3e9e1] bg-[#fbfcfa] px-4 py-3.5 text-[13px] leading-6 text-[#31483d] placeholder:text-[#a6b0a8] focus:border-[#89a27d] focus:bg-white focus:ring-4 focus:ring-[#7c9c6e]/10 focus:outline-none sm:min-h-[250px]"
                            @input="
                                failure = '';
                                notice = '';
                            "
                        ></textarea>

                        <div
                            v-if="failure && !draft"
                            role="alert"
                            class="mt-2 rounded-xl border border-[#f0d7d2] bg-[#fff7f5] px-3.5 py-2.5 text-xs leading-5 text-[#a94c40]"
                        >
                            {{ failure }}
                        </div>

                        <div
                            class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p
                                class="flex items-center gap-1.5 text-[11px] text-[#8a978d]"
                            >
                                <svg
                                    viewBox="0 0 16 16"
                                    fill="none"
                                    class="size-3.5"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M8 1.5 13 3.5v3.9c0 3-2.1 5.6-5 6.9-2.9-1.3-5-3.9-5-6.9V3.5l5-2Z"
                                        stroke="currentColor"
                                        stroke-width="1.2"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="m5.8 7.8 1.5 1.5 3-3.1"
                                        stroke="currentColor"
                                        stroke-width="1.2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                Your draft stays editable until you save.
                            </p>
                            <button
                                type="button"
                                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#1c493b] px-5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#15392f] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#58836e] disabled:cursor-wait disabled:opacity-65"
                                :disabled="busy"
                                @click="fillForm"
                            >
                                <svg
                                    v-if="busyAction === 'preview'"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                >
                                    <circle
                                        cx="10"
                                        cy="10"
                                        r="7"
                                        stroke="currentColor"
                                        stroke-opacity=".3"
                                        stroke-width="2"
                                    />
                                    <path
                                        d="M17 10a7 7 0 0 0-7-7"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    class="size-4"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="m10 2 1.9 5.1L17 9l-5.1 1.9L10 16l-1.9-5.1L3 9l5.1-1.9L10 2Z"
                                        stroke="currentColor"
                                        stroke-width="1.4"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="m15.5 13 .9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9.9-2.1Z"
                                        fill="currentColor"
                                    />
                                </svg>
                                {{
                                    busyAction === 'preview'
                                        ? 'Reading message…'
                                        : 'Fill intake form'
                                }}
                            </button>
                        </div>

                        <div class="mt-7 border-t border-[#edf0eb] pt-5">
                            <div class="mb-3 flex items-center justify-between">
                                <p
                                    class="text-[10px] font-bold tracking-[0.13em] text-[#94a097] uppercase"
                                >
                                    Or try a sample
                                </p>
                                <span class="text-[10px] text-[#aab3ab]"
                                    >{{ examples.length }} examples</span
                                >
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="example in examples"
                                    :key="example.id"
                                    type="button"
                                    class="rounded-lg border border-[#e7ebe4] bg-white px-2.5 py-2 text-left text-[11px] font-medium text-[#607369] transition hover:border-[#bfd0b6] hover:bg-[#f5f8f2] hover:text-[#355b46] focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[#7b9a70]"
                                    @click="chooseExample(example)"
                                >
                                    {{ example.title }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#fdfefc] p-5 sm:p-7 lg:p-8">
                        <div
                            class="mb-5 flex items-start justify-between gap-3"
                        >
                            <div>
                                <label
                                    class="text-[11px] font-bold tracking-[0.12em] text-[#688071] uppercase"
                                    >02 &nbsp; Review extracted fields</label
                                >
                                <p class="mt-1.5 text-xs text-[#8a978d]">
                                    Check the details before they become a
                                    ticket.
                                </p>
                            </div>
                            <span
                                v-if="
                                    isReady && Object.keys(errors).length === 0
                                "
                                class="flex shrink-0 items-center gap-1.5 rounded-full border border-[#dcead6] bg-[#f1f7ed] px-2.5 py-1.5 text-[10px] font-semibold text-[#527448]"
                            >
                                <span
                                    class="size-1.5 rounded-full bg-[#78a866]"
                                ></span>
                                READY TO SAVE
                            </span>
                            <span
                                v-else-if="isReady"
                                class="flex shrink-0 items-center gap-1.5 rounded-full border border-[#efdfc4] bg-[#fbf6eb] px-2.5 py-1.5 text-[10px] font-semibold text-[#a07a36]"
                            >
                                <span
                                    class="size-1.5 rounded-full bg-[#d1a44d]"
                                ></span>
                                NEEDS REVIEW
                            </span>
                            <span
                                v-else
                                class="rounded-full border border-[#e7ebe5] bg-white px-2.5 py-1.5 text-[10px] font-medium text-[#9aa69d]"
                                >WAITING FOR MESSAGE</span
                            >
                        </div>

                        <div v-if="draft" class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label
                                        for="customer"
                                        class="mb-1.5 block text-[11px] font-semibold text-[#566b5e]"
                                        >Customer name</label
                                    >
                                    <input
                                        id="customer"
                                        v-model="draft.customer"
                                        type="text"
                                        autocomplete="name"
                                        placeholder="Customer name"
                                        :class="[
                                            'field-input',
                                            fieldError('customer') &&
                                                'field-input-error',
                                        ]"
                                        @input="clearFieldError('customer')"
                                    />
                                    <p
                                        v-if="fieldError('customer')"
                                        class="mt-1 text-[11px] text-[#b44c42]"
                                    >
                                        {{ fieldError('customer') }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        for="email"
                                        class="mb-1.5 block text-[11px] font-semibold text-[#566b5e]"
                                        >Email address</label
                                    >
                                    <input
                                        id="email"
                                        v-model="draft.email"
                                        type="email"
                                        autocomplete="email"
                                        placeholder="name@company.com"
                                        :class="[
                                            'field-input',
                                            fieldError('email') &&
                                                'field-input-error',
                                        ]"
                                        @input="clearFieldError('email')"
                                    />
                                    <p
                                        v-if="fieldError('email')"
                                        class="mt-1 text-[11px] text-[#b44c42]"
                                    >
                                        {{ fieldError('email') }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        for="product"
                                        class="mb-1.5 block text-[11px] font-semibold text-[#566b5e]"
                                        >Product</label
                                    >
                                    <input
                                        id="product"
                                        v-model="draft.product"
                                        type="text"
                                        placeholder="Product or service"
                                        :class="[
                                            'field-input',
                                            fieldError('product') &&
                                                'field-input-error',
                                        ]"
                                        @input="clearFieldError('product')"
                                    />
                                    <p
                                        v-if="fieldError('product')"
                                        class="mt-1 text-[11px] text-[#b44c42]"
                                    >
                                        {{ fieldError('product') }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        for="urgency"
                                        class="mb-1.5 block text-[11px] font-semibold text-[#566b5e]"
                                        >Urgency</label
                                    >
                                    <select
                                        id="urgency"
                                        v-model="draft.urgency"
                                        :class="[
                                            'field-input',
                                            fieldError('urgency') &&
                                                'field-input-error',
                                        ]"
                                        @change="clearFieldError('urgency')"
                                    >
                                        <option :value="null" disabled>
                                            Select urgency
                                        </option>
                                        <option value="low">
                                            Low · when you can
                                        </option>
                                        <option value="normal">
                                            Normal · standard queue
                                        </option>
                                        <option value="high">
                                            High · needs attention
                                        </option>
                                    </select>
                                    <p
                                        v-if="fieldError('urgency')"
                                        class="mt-1 text-[11px] text-[#b44c42]"
                                    >
                                        {{ fieldError('urgency') }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label
                                    for="summary"
                                    class="mb-1.5 block text-[11px] font-semibold text-[#566b5e]"
                                    >Issue summary</label
                                >
                                <textarea
                                    id="summary"
                                    v-model="draft.summary"
                                    rows="2"
                                    placeholder="A concise description of the issue"
                                    :class="[
                                        'field-input min-h-[76px] resize-y',
                                        fieldError('summary') &&
                                            'field-input-error',
                                    ]"
                                    @input="clearFieldError('summary')"
                                ></textarea>
                                <p
                                    v-if="fieldError('summary')"
                                    class="mt-1 text-[11px] text-[#b44c42]"
                                >
                                    {{ fieldError('summary') }}
                                </p>
                            </div>

                            <div
                                v-if="failure"
                                role="alert"
                                class="rounded-xl border border-[#f0d7d2] bg-[#fff7f5] px-3.5 py-2.5 text-xs leading-5 text-[#a94c40]"
                            >
                                {{ failure }}
                            </div>
                            <div
                                v-if="notice"
                                role="status"
                                class="flex items-center gap-2 rounded-xl border border-[#dcead6] bg-[#f3f8ef] px-3.5 py-2.5 text-xs leading-5 text-[#4f7146]"
                            >
                                <svg
                                    viewBox="0 0 16 16"
                                    fill="none"
                                    class="size-4 shrink-0"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="m3 8.2 3.1 3.1L13 4.5"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                {{ notice }}
                            </div>

                            <div
                                class="flex flex-col gap-3 border-t border-[#edf0eb] pt-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <p
                                    class="max-w-[290px] text-[10px] leading-4 text-[#99a39b]"
                                >
                                    Nothing is saved automatically. You stay in
                                    control of what enters the queue.
                                </p>
                                <button
                                    type="button"
                                    class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-xl border border-[#dfe7dc] bg-white px-4 text-xs font-semibold text-[#3f624d] transition hover:border-[#bdcfb8] hover:bg-[#f6f9f3] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#71936a] disabled:cursor-not-allowed disabled:opacity-45"
                                    :disabled="
                                        busy ||
                                        Object.keys(errors).length > 0 ||
                                        savedCurrentDraft
                                    "
                                    @click="saveForm"
                                >
                                    <svg
                                        v-if="busyAction === 'save'"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        class="size-4 animate-spin"
                                        aria-hidden="true"
                                    >
                                        <circle
                                            cx="10"
                                            cy="10"
                                            r="7"
                                            stroke="currentColor"
                                            stroke-opacity=".3"
                                            stroke-width="2"
                                        />
                                        <path
                                            d="M17 10a7 7 0 0 0-7-7"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        class="size-4"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M3 3.5h11l3 3V16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V3.5Z"
                                            stroke="currentColor"
                                            stroke-width="1.4"
                                            stroke-linejoin="round"
                                        />
                                        <path
                                            d="M6 3.5v5h7v-5M6.5 17v-5h7v5"
                                            stroke="currentColor"
                                            stroke-width="1.4"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    {{
                                        busyAction === 'save'
                                            ? 'Saving…'
                                            : savedCurrentDraft
                                              ? 'Saved to Harbor Desk'
                                              : 'Confirm & save intake'
                                    }}
                                </button>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex min-h-[350px] flex-col items-center justify-center rounded-2xl border border-dashed border-[#dde5db] bg-[#fafcf9] px-6 text-center sm:min-h-[390px]"
                        >
                            <span
                                class="mb-4 flex size-12 items-center justify-center rounded-2xl border border-[#e4ebe0] bg-white text-[#7d9672] shadow-sm"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-5"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M7 4.5h10M7 8.5h10M7 12.5h6M5.5 2.5h13a1.5 1.5 0 0 1 1.5 1.5v16a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 20V4a1.5 1.5 0 0 1 1.5-1.5Z"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </span>
                            <p class="text-sm font-semibold text-[#456052]">
                                Your intake draft will appear here
                            </p>
                            <p
                                class="mt-1.5 max-w-[270px] text-xs leading-5 text-[#929f95]"
                            >
                                Paste a message or select a sample, then let the
                                assistant organize the details.
                            </p>
                            <div
                                class="mt-6 flex items-center gap-2 text-[10px] font-medium tracking-[0.08em] text-[#9ba79d] uppercase"
                            >
                                <span>Customer</span
                                ><span class="text-[#c4cdc3]">→</span
                                ><span>Product</span
                                ><span class="text-[#c4cdc3]">→</span
                                ><span>Urgency</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-[#e9ece6] bg-[#fbfcfa] px-5 py-3.5 sm:px-7"
                >
                    <p
                        class="flex items-center gap-2 text-[10px] text-[#919d94]"
                    >
                        <svg
                            viewBox="0 0 16 16"
                            fill="none"
                            class="size-3.5 text-[#829b78]"
                            aria-hidden="true"
                        >
                            <path
                                d="M8 1.5 13 3.5v3.9c0 3-2.1 5.6-5 6.9-2.9-1.3-5-3.9-5-6.9V3.5l5-2Z"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linejoin="round"
                            />
                            <path
                                d="m5.8 7.8 1.5 1.5 3-3.1"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        AI suggests. A human approves. Required fields are
                        validated before saving.
                    </p>
                    <p class="text-[10px] text-[#a0aaa2]">
                        DEMO WORKSPACE <span class="mx-1.5">·</span> FICTIONAL
                        DATA ONLY
                    </p>
                </div>
            </section>

            <section
                class="mt-9 grid gap-5 lg:grid-cols-[0.8fr_1.2fr] lg:gap-8"
            >
                <div
                    class="rounded-2xl border border-[#e3e8df] bg-white/70 p-5 sm:p-6"
                >
                    <p
                        class="text-[10px] font-bold tracking-[0.13em] text-[#84958a] uppercase"
                    >
                        A small, deliberate workflow
                    </p>
                    <div class="mt-4 space-y-4">
                        <div class="flex gap-3">
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-[#edf3e8] text-[10px] font-bold text-[#5d7a50]"
                                >01</span
                            >
                            <div>
                                <p class="text-xs font-semibold text-[#3c5548]">
                                    Extract, don’t guess
                                </p>
                                <p
                                    class="mt-1 text-[11px] leading-5 text-[#89968d]"
                                >
                                    An LLM structures the note. Unknown details
                                    stay blank.
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-[#eaf2ef] text-[10px] font-bold text-[#4e7669]"
                                >02</span
                            >
                            <div>
                                <p class="text-xs font-semibold text-[#3c5548]">
                                    Validate before save
                                </p>
                                <p
                                    class="mt-1 text-[11px] leading-5 text-[#89968d]"
                                >
                                    Required fields and email format are checked
                                    server-side.
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-[#f5efe2] text-[10px] font-bold text-[#987a42]"
                                >03</span
                            >
                            <div>
                                <p class="text-xs font-semibold text-[#3c5548]">
                                    Keep a human in control
                                </p>
                                <p
                                    class="mt-1 text-[11px] leading-5 text-[#89968d]"
                                >
                                    Review or edit every suggestion before
                                    confirming.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-[#e3e8df] bg-white/70 p-5 sm:p-6"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p
                                class="text-[10px] font-bold tracking-[0.13em] text-[#84958a] uppercase"
                            >
                                Recent confirmed intake
                            </p>
                            <p class="mt-1 text-xs text-[#89968d]">
                                Saved forms in this demo workspace
                            </p>
                        </div>
                        <span
                            class="rounded-lg bg-[#f1f4ee] px-2 py-1 text-[10px] font-semibold text-[#6d8173]"
                            >{{ recentForms.length }} SAVED</span
                        >
                    </div>
                    <div
                        v-if="recentForms.length"
                        class="mt-4 divide-y divide-[#edf0eb]"
                    >
                        <div
                            v-for="form in recentForms"
                            :key="form.id"
                            class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate text-xs font-semibold text-[#40584c]"
                                >
                                    {{ form.customer }}
                                </p>
                                <p
                                    class="mt-1 truncate text-[10px] text-[#95a097]"
                                >
                                    {{ form.product }}
                                    <span class="mx-1">·</span> {{ form.email }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'shrink-0 rounded-md px-2 py-1 text-[9px] font-bold tracking-[0.07em] uppercase',
                                    form.urgency === 'high'
                                        ? 'bg-[#fbefeb] text-[#ad6653]'
                                        : form.urgency === 'low'
                                          ? 'bg-[#f3f2e9] text-[#8b835c]'
                                          : 'bg-[#edf4eb] text-[#6e8a62]',
                                ]"
                                >{{ form.urgency }}</span
                            >
                        </div>
                    </div>
                    <div
                        v-else
                        class="mt-4 rounded-xl bg-[#f8faf7] px-4 py-5 text-center text-xs text-[#91a096]"
                    >
                        No confirmed intakes yet. Try one of the sample messages
                        above.
                    </div>
                </div>
            </section>

            <footer
                class="mt-9 flex flex-col items-center justify-between gap-2 border-t border-[#e2e7df] pt-5 text-[10px] text-[#a0aaa2] sm:flex-row"
            >
                <p>
                    Harbor Desk AI intake prototype
                    <span class="mx-1.5">·</span> Built for thoughtful support
                    teams
                </p>
                <p>
                    Laravel <span class="mx-1">×</span> Vue
                    <span class="mx-1">×</span> LLM extraction
                </p>
            </footer>
        </main>
    </div>
</template>

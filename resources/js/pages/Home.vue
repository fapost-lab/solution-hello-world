<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Button } from '@fapost/ui/components/button'

const props = defineProps<{ title: string; greeting: string }>()

const clicks = ref(0)
</script>

<template>
    <section class="hello-home">
        <Head :title="props.title" />
        <h1 class="hello-home__title">{{ props.greeting }}</h1>
        <Button @click="clicks++">Clicked {{ clicks }}</Button>
    </section>
</template>

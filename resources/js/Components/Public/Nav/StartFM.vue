<template>
  <!-- No animation on Safari, if NButton has 'text' attribute -->
  <NPopover>
    <template #trigger>
      <NButton v-bind="$attrs" text :loading title="StartFM" @click="toggleAudio">
        <template #icon>
          <IFluentPause24Filled v-if="audioPlaying" />
          <IFluentMusicNote2Play20Filled v-else />
        </template>
        <slot />
        <audio v-show="false" ref="startFM" preload="none" @canplay="changeLoading">
          <source src="https://eteris.startfm.lt/startfm.mp3" type="audio/mpeg">
          <source src="https://eteris.startfm.lt/startfm.m4a" type="audio/mp4">
        </audio>
      </NButton>
    </template>
    {{ $t("Klausykis studentiško") }}
    <a class="font-bold transition hover:text-vusa-red" href="https://startfm.lt" target="_blank">START FM</a>
    {{ $t("radijo") }}!
  </NPopover>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { ref, useTemplateRef } from "vue";

const startFM = useTemplateRef<HTMLAudioElement>('startFM');

const audioPlaying = ref(false);
const loading = ref(false);

const changeLoading = () => {
  loading.value = false;
  audioPlaying.value = true;
};

const toggleAudio = () => {
  if (startFM.value?.paused) {
    startFM.value?.play();

    if (startFM.value.readyState !== 4) {
      loading.value = true;
    } else {
      changeLoading();
    }
  } else {
    startFM.value?.pause();
    audioPlaying.value = false;
  }
};
</script>

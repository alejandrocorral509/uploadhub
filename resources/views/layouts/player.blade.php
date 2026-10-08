{{-- Reproductor global. data-turbo-permanent hace que este elemento (y el <audio>) sobreviva a la navegación. --}}
<div id="global-player" data-turbo-permanent x-data="playerBar" class="contents">
    <audio x-ref="audio" id="player-audio" preload="metadata" class="hidden"></audio>

    <section x-show="$store.player.current" x-cloak x-transition:enter="transition duration-300" x-transition:enter-start="translate-y-full"
             class="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white/95 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] backdrop-blur"
             aria-label="Reproductor">
        <template x-if="$store.player.current">
            <div class="mx-auto max-w-7xl px-3 py-2 sm:px-6">
                {{-- Barra de progreso: funciona con ratón, dedo y teclado (flechas) --}}
                <div class="flex items-center gap-2 text-[11px] tabular-nums text-gray-500">
                    <span x-text="$store.player.formatTime($store.player.position)" class="w-12 text-right"></span>
                    <input type="range" min="0" step="1" class="h-1.5 flex-1 cursor-pointer accent-brand-600"
                           :max="Math.max(1, Math.floor($store.player.duration))"
                           :value="Math.floor($store.player.position)"
                           @input="onScrub" @change="onScrubEnd"
                           aria-label="Posición del episodio">
                    <span x-text="$store.player.formatTime($store.player.duration)" class="w-12"></span>
                </div>

                <div class="mt-1 flex items-center gap-3">
                    {{-- Episodio actual --}}
                    <a :href="$store.player.current.url" class="flex min-w-0 flex-1 items-center gap-3">
                        <template x-if="$store.player.current.cover">
                            <img :src="$store.player.current.cover" alt="" class="size-11 shrink-0 rounded-lg object-cover">
                        </template>
                        <template x-if="! $store.player.current.cover">
                            <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-fuchsia-500 text-white">
                                <i class="fa-solid fa-microphone"></i>
                            </div>
                        </template>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold" x-text="$store.player.current.title"></p>
                            <p class="truncate text-xs text-gray-500" x-text="$store.player.error ?? $store.player.current.author"
                               :class="$store.player.error && 'text-red-600'"></p>
                        </div>
                    </a>

                    {{-- Controles --}}
                    <div class="flex items-center gap-1 sm:gap-2">
                        <button type="button" @click="$store.player.skip(-15)" class="btn-icon size-9 text-gray-600 hover:bg-gray-100" title="Retroceder 15 segundos" aria-label="Retroceder 15 segundos">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                        <button type="button" @click="$store.player.toggle()" class="btn-icon size-12 bg-brand-600 text-white shadow hover:bg-brand-700"
                                :aria-label="$store.player.playing ? 'Pausar' : 'Reproducir'">
                            <i class="fa-solid" :class="$store.player.loading ? 'fa-spinner fa-spin' : ($store.player.playing ? 'fa-pause' : 'fa-play ml-0.5')"></i>
                        </button>
                        <button type="button" @click="$store.player.skip(30)" class="btn-icon size-9 text-gray-600 hover:bg-gray-100" title="Avanzar 30 segundos" aria-label="Avanzar 30 segundos">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                    </div>

                    <button type="button" @click="$store.player.cycleSpeed()" class="btn btn-ghost btn-sm w-12 shrink-0 px-1 tabular-nums" title="Velocidad de reproducción"
                            x-text="$store.player.speed + '×'" aria-label="Cambiar velocidad"></button>

                    <div class="hidden flex-1 items-center justify-end gap-2 sm:flex">

                        <div class="hidden items-center gap-2 md:flex">
                            <button type="button" @click="$store.player.toggleMute()" class="btn-icon size-9 text-gray-600 hover:bg-gray-100" aria-label="Silenciar">
                                <i class="fa-solid" :class="$store.player.muted || $store.player.volume === 0 ? 'fa-volume-xmark' : 'fa-volume-high'"></i>
                            </button>
                            <input type="range" min="0" max="1" step="0.05" class="w-20 accent-brand-600" :value="$store.player.muted ? 0 : $store.player.volume"
                                   @input="$store.player.setVolume($event.target.value)" aria-label="Volumen">
                        </div>

                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @click="open = ! open" class="btn-icon relative size-9 text-gray-600 hover:bg-gray-100" aria-label="Cola de reproducción">
                                <i class="fa-solid fa-list-ul"></i>
                                <span x-show="$store.player.queue.length" x-text="$store.player.queue.length"
                                      class="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-brand-600 text-[10px] font-bold text-white"></span>
                            </button>
                            <div x-show="open" x-cloak x-transition @click.outside="open = false"
                                 class="absolute right-0 bottom-12 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2">
                                    <p class="text-sm font-semibold">A continuación</p>
                                    <button type="button" x-show="$store.player.queue.length" @click="$store.player.clearQueue()" class="text-xs text-gray-500 hover:text-red-600">Vaciar</button>
                                </div>
                                <ul class="max-h-72 overflow-y-auto">
                                    <template x-for="(episode, index) in $store.player.queue" :key="episode.id">
                                        <li class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50">
                                            <button type="button" class="min-w-0 flex-1 text-left" @click="$store.player.queue.splice(index, 1); $store.player.load(episode)">
                                                <p class="truncate text-sm font-medium" x-text="episode.title"></p>
                                                <p class="truncate text-xs text-gray-500" x-text="episode.author"></p>
                                            </button>
                                            <button type="button" @click="$store.player.removeFromQueue(index)" class="text-gray-400 hover:text-red-600" aria-label="Quitar de la cola">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </li>
                                    </template>
                                    <li x-show="! $store.player.queue.length" class="px-4 py-6 text-center text-sm text-gray-500">
                                        La cola está vacía. Usa <i class="fa-solid fa-list-ul"></i> en cualquier episodio para añadirlo.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="$store.player.close()" class="btn-icon size-8 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Cerrar reproductor">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </template>
    </section>
</div>

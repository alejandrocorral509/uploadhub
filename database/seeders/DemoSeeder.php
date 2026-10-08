<?php

namespace Database\Seeders;

use App\Models\Audio;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Play;
use App\Models\PlaybackProgress;
use App\Models\Show;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de demostración: usuarios, programas, episodios con audio, escuchas y comentarios.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Solo para desarrollo o una demo: los usuarios tienen una contraseña conocida
 * (DEMO_PASSWORD en el .env, o "demo-uploadhub" si no se define).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesSeeder::class, CategoriesSeeder::class]);

        $password = env('DEMO_PASSWORD') ?: 'demo-uploadhub';
        $dir = database_path('seeders/demo');

        $users = collect([
            ['Laura Gómez', 'laura@demo.test', 'Desarrolladora backend. Hablo de Laravel, bases de datos y de cómo no romper producción un viernes.'],
            ['Dani Ruiz', 'dani@demo.test', 'Periodista deportivo y fan del fútbol modesto.'],
            ['Marta Vidal', 'marta@demo.test', 'Historiadora. Cuento historias del Mediterráneo para escuchar paseando.'],
            ['Pablo Sanz', 'pablo@demo.test', null],
        ])->map(fn ($u) => User::firstOrCreate(['email' => $u[1]], ['name' => $u[0], 'password' => $password, 'bio' => $u[2]]));

        [$laura, $dani, $marta, $pablo] = $users->all();
        $cat = fn (string $name) => Category::where('name', $name)->value('id');

        $shows = [
            'codigo' => $this->show($laura, 'Código y café', 'Un podcast semanal sobre desarrollo web con Laravel, PHP y buenas prácticas, en el tiempo que dura un café.', 'Tecnología', 1),
            'previa' => $this->show($dani, 'La previa', 'Análisis de la jornada, entrevistas y las historias que no salen en la tele.', 'Deportes', 2),
            'mediterraneo' => $this->show($marta, 'Historias del Mediterráneo', 'Puertos, naufragios y comerciantes: la historia que pasó cerca del mar.', 'Historia', 3),
        ];

        $episodes = [
            [$shows['codigo'], 'Laravel desde cero: rutas y controladores', 'Repasamos cómo llega una petición a tu aplicación y cómo organizar los controladores para que no crezcan sin control.', ['laravel', 'php', 'backend'], 1, 1, 1],
            [$shows['codigo'], 'Eloquent sin sustos: el problema N+1', 'Por qué tu página hace 300 consultas, cómo detectarlo y cómo arreglarlo con eager loading.', ['laravel', 'bases de datos'], 1, 2, 2],
            [$shows['codigo'], 'Tests con Pest para gente con prisa', 'Escribimos los primeros tests de una app real y vemos qué merece la pena probar.', ['testing', 'pest'], 1, 3, 3],
            [$shows['previa'], 'Jornada 8: el líder tropieza', 'Las claves del fin de semana y nuestras apuestas para la siguiente jornada.', ['fútbol', 'liga'], 2, 1, 4],
            [$shows['previa'], 'Entrevista: la vida en Segunda B', 'Charlamos con un jugador sobre viajes en autobús, sueldos y vocación.', ['entrevista', 'fútbol'], 2, 2, 5],
            [$shows['mediterraneo'], 'El puerto de Cartagena en el siglo XVIII', 'Cómo era la vida en uno de los arsenales más importantes de Europa.', ['historia', 'cartagena'], 3, 1, 6],
            [$shows['mediterraneo'], 'Naufragios: lo que guarda el fondo', 'Arqueología submarina y los barcos que nunca llegaron a puerto.', ['historia', 'arqueología'], 3, 2, 7],
            [null, 'Notas de voz: ideas para un nuevo programa', 'Un borrador privado para probar el sonido antes de lanzar el programa.', ['borrador'], 1, null, 8, 'private'],
        ];

        $created = collect();
        foreach ($episodes as $i => $e) {
            [$show, $title, $description, $tags, $categorySeed, $number, $file] = $e;
            $owner = $show?->user ?? $laura;

            $path = 'audios/demo-'.($i + 1).'.mp3';
            Storage::disk(config('media.disk'))->put($path, file_get_contents("{$dir}/ep{$file}.mp3"));

            $audio = Audio::updateOrCreate(['title' => $title, 'user_id' => $owner->id], [
                'show_id' => $show?->id,
                'season' => $show ? 1 : null,
                'episode_number' => $number,
                'description' => $description,
                'filepath' => $path,
                'filetype' => 'audio/mpeg',
                'privacy' => $e[7] ?? 'public',
                'processing_status' => Audio::STATUS_READY,
                'duration' => 40 + $file * 15, // lo que dura el archivo de demo
                'waveform' => $this->fakeWaveform($i),
            ]);
            $audio->created_at = now()->subDays(40 - $i * 5);
            $audio->save();

            $audio->categories()->sync([$show?->category_id ?? $cat('Cultura')]);
            $audio->tags()->sync(collect($tags)->map(fn ($t) => Tag::firstOrCreate(['name' => $t])->id));
            $created->push($audio);
        }

        // Un episodio con transcripción, resumen y capítulos de ejemplo (lo que genera la IA)
        $created[1]->update([
            'transcript_status' => 'ready',
            'transcript' => 'Hola y bienvenidos a Código y café. Hoy hablamos del problema N+1, el error de rendimiento más habitual en aplicaciones con un ORM. '
                .'Ocurre cuando cargas una lista de modelos y, para cada uno, haces una consulta más para traer una relación. Con diez episodios no se nota, pero con mil la página tarda segundos. '
                .'La solución es el eager loading: con el método with le dices a Eloquent qué relaciones vas a necesitar y las trae todas en una sola consulta. '
                .'Un truco muy útil es activar preventLazyLoading en desarrollo. Así, si se te olvida un with, la aplicación lanza un error y lo ves enseguida en lugar de en producción. '
                .'Para terminar, revisamos cómo medir el número de consultas con el registro de consultas de Laravel y con la barra de depuración. Nos escuchamos la semana que viene.',
            'summary' => 'Laura explica qué es el problema N+1 en Eloquent, por qué hace que las páginas sean lentas cuando crecen los datos y cómo resolverlo con eager loading. También recomienda activar preventLazyLoading en desarrollo para detectar el fallo pronto y muestra cómo medir las consultas.',
            'chapters' => [
                ['start' => 0, 'title' => 'Presentación'],
                ['start' => 8, 'title' => 'Qué es el problema N+1'],
                ['start' => 25, 'title' => 'Eager loading con with()'],
                ['start' => 38, 'title' => 'preventLazyLoading en desarrollo'],
                ['start' => 50, 'title' => 'Medir las consultas'],
            ],
        ]);

        // Me gusta, suscripciones, comentarios
        $public = $created->where('privacy', 'public');
        foreach ($users as $u) {
            $public->random(4)->each(fn ($a) => $a->likes()->syncWithoutDetaching([$u->id]));
        }
        $pablo->subscriptions()->syncWithoutDetaching([$shows['codigo']->id, $shows['mediterraneo']->id]);
        $dani->subscriptions()->syncWithoutDetaching([$shows['codigo']->id]);
        $marta->subscriptions()->syncWithoutDetaching([$shows['previa']->id]);

        $comments = [
            [$created[1], $pablo, 'Me ha salvado la semana: tenía una página con 400 consultas y no sabía por qué.'],
            [$created[1], $dani, '¡Muy claro! ¿Haréis uno sobre colas y jobs?'],
            [$created[1], $laura, '¡Apuntado para el próximo episodio, Dani!'],
            [$created[3], $pablo, 'La previa de los lunes ya es tradición.'],
            [$created[5], $laura, 'No sabía nada de esto y me ha encantado. Más episodios así, por favor.'],
        ];
        foreach ($comments as [$audio, $user, $content]) {
            Comment::firstOrCreate(['audio_id' => $audio->id, 'user_id' => $user->id, 'content' => $content]);
        }

        // Escuchas de los últimos 30 días, para que las estadísticas tengan datos
        if (Play::count() === 0) {
            foreach ($public as $audio) {
                $count = random_int(15, 60);
                for ($p = 0; $p < $count; $p++) {
                    // La mayoría se va antes del final: así la curva de retención baja como en la realidad
                    $reach = min(1, max(0.05, 1 - (random_int(0, 100) / 100) ** 0.7));
                    $play = Play::create([
                        'audio_id' => $audio->id,
                        'listener_hash' => hash('sha256', 'demo'.random_int(1, 40)),
                        'max_position' => (int) round($audio->duration * $reach),
                    ]);
                    $play->created_at = now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 1440));
                    $play->save();
                }
                $audio->update(['plays_count' => $count]);
            }

            PlaybackProgress::updateOrCreate(['user_id' => $pablo->id, 'audio_id' => $created[2]->id], ['position' => 40, 'completed' => false]);
        }

        $this->command?->info('Demo lista. Usuarios: laura@demo.test, dani@demo.test, marta@demo.test, pablo@demo.test (contraseña: DEMO_PASSWORD o la de por defecto en DemoSeeder).');
    }

    private function show(User $user, string $title, string $description, string $category, int $cover): Show
    {
        $show = Show::firstOrCreate(['user_id' => $user->id, 'title' => $title], [
            'description' => $description,
            'category_id' => Category::where('name', $category)->value('id'),
            'language' => 'es',
        ]);

        if (! $show->cover) {
            $path = 'covers/demo-show-'.$cover.'.jpg';
            Storage::disk(config('media.covers_disk'))->put($path, file_get_contents(database_path("seeders/demo/cover{$cover}.jpg")));
            $show->update(['cover' => $path]);
        }

        return $show->setRelation('user', $user);
    }

    // Forma de onda verosímil para la demo (sin necesitar ffmpeg)
    private function fakeWaveform(int $seed): array
    {
        mt_srand($seed + 1);

        return array_map(fn ($i) => (int) min(100, round(35 + 45 * abs(sin($i / 7 + $seed)) + mt_rand(0, 20))), range(0, config('media.waveform_points') - 1));
    }
}

<?php

use App\Support\ChartScale;

test('redondea el máximo del eje a un valor limpio', function (float $max, float $expectedMax) {
    expect(ChartScale::nice($max)['max'])->toEqual($expectedMax);
})->with([
    [7, 8],
    [23, 30],
    [90, 100],
    [230, 300],
    [95, 100],
    [96, 100],
    [1234, 1500],
]);

test('sin datos devuelve una escala por defecto', function () {
    expect(ChartScale::nice(0)['ticks'])->toBe([0, 1, 2, 3, 4]);
});

test('nunca hay marcas con decimales', function () {
    foreach ([...ChartScale::nice(3)['ticks'], ...ChartScale::nice(9)['ticks']] as $tick) {
        expect(fmod($tick, 1))->toEqual(0.0);
    }
});

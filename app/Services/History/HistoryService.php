<?php

namespace App\Services\History;

use Exception;

/**
 * Klasa serwisu do znajdowania różnic pomiędzy dwoma obiektami.
 */
class HistoryService
{
    /**
     * Maksymalny poziom zagnieżdżenia się rekurencyjnego szukania różnic
     */
    public const MAX_DEEP = 5;

    /**
     * Klucz główny, po którym identyfikowane są zmiany w tablicy elementów.
     */
    public const ARRAY_PRIMARY_KEY = 'id';

    /**
     * Klucz wykorzystywany jako etykieta, który nie jest brany pod uwagę, podczas analizy różnic.
     */
    public const OBJECT_LABEL = 'label';

    /**
     * Zwraca tablice różnic pomiędzy stary i nowym obiektem
     * lub null w przypadku braku różnic.
     */
    public static function diff(?string $old, ?string $new): ?array
    {
        return self::compare(
            json_decode($old),
            json_decode($new)
        );
    }

    /**
     * Porównuje rekurencyjnie dwa obiekty ze sobą i zwraca różnicę.
     *
     * @param  mixed  $old
     * @param  mixed  $new
     * @param  int  $deep
     *
     * @throws Exception
     */
    protected static function compare($old, $new, $deep = 1): ?array
    {
        if ($deep > self::MAX_DEEP) {
            return null;
        }
        if (
            ($old !== null && ! is_object($old))
            || ($new !== null && ! is_object($new))
        ) {
            throw new Exception('CAN_NOT_COMPARE_DATA_TO_MAKE_HISTORY!');
        }
        $main = $old ?? $new;
        if ($main === null) {
            return null;
        }

        $diff = [];
        foreach ($main as $key => $value) {
            if ($key === self::ARRAY_PRIMARY_KEY) {
                continue;
            }
            $oldValue = self::getAttribute($old, $key);
            $newValue = self::getAttribute($new, $key);
            if (is_object($oldValue) || is_object($newValue)) {
                $tmp = self::compare($oldValue, $newValue, $deep + 1);
                if ($tmp !== null && count($tmp) > 0) {
                    $diff[$key] = $tmp;
                }
            } elseif (is_array($oldValue) || is_array($newValue)) {
                $tmp = self::iterate($oldValue, $newValue, $deep + 1);
                if ($tmp !== null && count($tmp) > 0) {
                    $diff[$key] = $tmp;
                }
            } else {
                if ($key === self::OBJECT_LABEL) {
                    $diff[$key] = (object) [
                        'old' => null,
                        'new' => null,
                        'label' => $oldValue !== null ? $oldValue : $newValue,
                    ];
                } else {
                    if ($oldValue !== $newValue) {
                        $diff[$key] = (object) [
                            'old' => $oldValue,
                            'new' => $newValue,
                        ];
                    }
                }

            }
        }

        $countDiff = 0;
        foreach ($diff as $item) {
            if (! isset($item->label)) {
                $countDiff++;
            }
        }

        return count($diff) > 0 && $countDiff > 0 ? $diff : null;
    }

    protected static function getAttribute($object, $attributeName): ?string
    {
        return $object->$attributeName ?? null;
    }

    /**
     * @throws Exception
     */
    protected static function iterate(?array $old, ?array $new, $deep): ?array
    {
        $oldItems = collect($old)->keyBy(self::ARRAY_PRIMARY_KEY);
        $newItems = collect($new)->keyBy(self::ARRAY_PRIMARY_KEY);
        $ids = $oldItems->keys()->merge($newItems->keys());
        $diff = [];
        foreach ($ids as $id) {
            $old = $oldItems->pull($id);
            $new = $newItems->pull($id);
            $main = $old ?? $new;
            $tmp = self::compare(
                $old,
                $new,
                $deep
            );
            if ($tmp !== null && count($tmp) > 0) {
                $diff[] = $tmp;
            }
        }

        return count($diff) > 0 ? $diff : null;
    }
}

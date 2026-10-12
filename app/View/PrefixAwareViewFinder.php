<?php

namespace App\View;

use Illuminate\View\FileViewFinder;
use InvalidArgumentException;

class PrefixAwareViewFinder extends FileViewFinder
{
    /**
     * Find the given view by name.
     * Supports both prefixed ('purchase.t_orders.index') and unprefixed ('purchase.orders.index') names.
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        try {
            return parent::find($name);
        } catch (InvalidArgumentException $e) {
            // Check fallback prefixes: t_ (transaksi), l_ (laporan), m_ (master)
            $segments = explode('.', $name);
            if (count($segments) >= 2) {
                foreach (['t_', 'l_', 'm_'] as $prefix) {
                    $prefixedSegments = $segments;
                    // Prepend prefix to the target folder/file segment
                    $prefixedSegments[1] = $prefix . $prefixedSegments[1];
                    $candidateName = implode('.', $prefixedSegments);
                    try {
                        return parent::find($candidateName);
                    } catch (InvalidArgumentException) {
                        // Continue checking next candidate prefix
                    }
                }
            }

            // Also check if prefix is being stripped (e.g. t_orders -> orders)
            if (count($segments) >= 2) {
                foreach (['t_', 'l_', 'm_'] as $prefix) {
                    if (str_starts_with($segments[1], $prefix)) {
                        $strippedSegments = $segments;
                        $strippedSegments[1] = substr($strippedSegments[1], strlen($prefix));
                        $candidateName = implode('.', $strippedSegments);
                        try {
                            return parent::find($candidateName);
                        } catch (InvalidArgumentException) {
                            // Continue
                        }
                    }
                }
            }

            throw $e;
        }
    }
}

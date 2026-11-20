<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GeoService
{
    /**
     * Validate and decode a GeoJSON geometry string.
     * Supports Point, LineString, Polygon.
     *
     * @throws InvalidArgumentException
     */
    public function parseGeometry(string $geojson): array
    {
        $data = json_decode($geojson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid GeoJSON: JSON decode error.');
        }

        if (! isset($data['type'])) {
            throw new InvalidArgumentException('Invalid GeoJSON: missing "type" field.');
        }

        $allowed = ['Point', 'LineString', 'Polygon', 'MultiPolygon'];
        if (! in_array($data['type'], $allowed, true)) {
            throw new InvalidArgumentException("Unsupported geometry type: {$data['type']}.");
        }

        if (! isset($data['coordinates'])) {
            throw new InvalidArgumentException('Invalid GeoJSON: missing "coordinates" field.');
        }

        return $data;
    }

    /**
     * Convert a GeoJSON geometry array to a PostGIS-compatible WKT expression
     * ready for use in a raw DB query.
     *
     * Usage:
     *   DB::statement("UPDATE risk_zones SET geometry = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326) WHERE id = ?",
     *       [$geojson, $id]);
     */
    public function toPostGisExpression(string $geojsonString): string
    {
        // ST_GeomFromGeoJSON accepts the GeoJSON geometry object (not a Feature)
        return $geojsonString;
    }

    /**
     * Insert or update a geometry column on a table row using PostGIS.
     */
    public function setGeometry(string $table, string $id, string $geojsonGeometry): void
    {
        DB::statement(
            "UPDATE {$table} SET geometry = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326) WHERE id = ?",
            [$geojsonGeometry, $id]
        );
    }

    /**
     * Build a GeoJSON FeatureCollection from an array of feature arrays.
     */
    public function toFeatureCollection(array $features): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    /**
     * Validate that GeoJSON coordinates use [longitude, latitude] order
     * and that longitude/latitude values are in valid ranges.
     *
     * @throws InvalidArgumentException
     */
    public function validateCoordinates(array $geometry): void
    {
        $coords = $geometry['coordinates'] ?? [];

        match ($geometry['type']) {
            'Point' => $this->assertPoint($coords),
            'LineString' => array_walk($coords, fn ($c) => $this->assertPoint($c)),
            'Polygon' => array_walk($coords[0] ?? [], fn ($c) => $this->assertPoint($c)),
            default => null,
        };
    }

    private function assertPoint(array $coord): void
    {
        [$lng, $lat] = $coord;
        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException("Longitude out of range: {$lng}.");
        }
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException("Latitude out of range: {$lat}.");
        }
    }

    /**
     * Find entities within a given distance (in degrees, approx.) from a point.
     * Wraps ST_DWithin for use in controllers.
     */
    public function scopeWithinDistance($query, float $longitude, float $latitude, float $distanceDegrees)
    {
        return $query->whereRaw(
            'ST_DWithin(geometry, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?)',
            [$longitude, $latitude, $distanceDegrees]
        );
    }
}

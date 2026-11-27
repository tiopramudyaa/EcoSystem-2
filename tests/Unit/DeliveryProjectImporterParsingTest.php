<?php

namespace Tests\Unit;

use App\Services\DeliveryProjectImporter as I;
use PHPUnit\Framework\TestCase;

/**
 * Pembacaan nilai & header pada import Delivery Project (tanpa database).
 */
class DeliveryProjectImporterParsingTest extends TestCase
{
    public function test_dates_from_excel_serial_and_common_text_formats(): void
    {
        $this->assertSame('2026-01-01', I::parseDate(46023));      // nomor seri Excel
        $this->assertSame('2026-01-01', I::parseDate('1-Jan-26'));  // tampilan template
        $this->assertSame('2026-01-31', I::parseDate('31/01/2026'));
        $this->assertSame('2026-01-31', I::parseDate('2026-01-31'));
        $this->assertSame('2025-10-24', I::parseDate('24-Oct-25'));
        $this->assertNull(I::parseDate('31/13/2026'));
        $this->assertNull(I::parseDate('tomorrow-ish'));
        $this->assertNull(I::parseDate(22));                        // bukan tanggal
    }

    public function test_numbers_in_various_formats(): void
    {
        $this->assertSame(1067000000.0, I::parseNumber(1067000000));
        $this->assertSame(1067000000.0, I::parseNumber('1,067,000,000'));
        $this->assertSame(1067000000.0, I::parseNumber('Rp 1.067.000.000'));
        $this->assertSame(27930750.5, I::parseNumber('27.930.750,5'));
        $this->assertSame(12.5, I::parseNumber('12,5'));
        $this->assertNull(I::parseNumber('abc'));
    }

    public function test_phone_numbers_are_not_turned_into_scientific_notation(): void
    {
        $this->assertSame('6282120000000', I::parsePhone(6282120000000.0));
        $this->assertSame('6282120000000', I::parsePhone(6282120000000));
        $this->assertSame('+62 812 3456', I::parsePhone('+62 812 3456'));
    }

    public function test_coordinates_from_text_and_google_maps_links(): void
    {
        $this->assertSame([-6.3032084, 106.8429761], I::parseCoordinates('-6.3032084,106.8429761'));
        $this->assertSame([-6.2, 106.8], I::parseCoordinates('https://www.google.com/maps/@-6.2,106.8,17z'));
        $this->assertSame([-6.2, 106.8], I::parseCoordinates('https://maps.google.com/?q=-6.2, 106.8'));
        $this->assertNull(I::parseCoordinates('Jakarta'));
    }

    public function test_header_row_is_found_below_the_group_title_row(): void
    {
        $rows = [
            [null, 'Project Form', null, null, 'Delivery Information'],
            ['No', 'Customer/Client *', 'Project Owner *', 'Project Name *', 'Revenue (Rp)'],
            [1, 'SML', 'A', 'B', 100],
        ];

        [$index, $cols] = I::locateHeader($rows);

        $this->assertSame(1, $index);
        $this->assertSame(1, $cols['customer']);
        $this->assertSame(3, $cols['name']);
        $this->assertSame(4, $cols['revenue']);
        $this->assertArrayNotHasKey('description', $cols);
    }

    public function test_same_named_customers_resolve_to_the_parent(): void
    {
        $parent = ['id' => 103, 'code' => 'SML', 'parent' => null];
        $child  = ['id' => 142, 'code' => 'SINERGI', 'parent' => 103];
        $other  = ['id' => 200, 'code' => 'X', 'parent' => null];

        $this->assertSame(103, I::parentOf([$parent, $child]));
        $this->assertSame(103, I::parentOf([$child, $parent]));
        $this->assertNull(I::parentOf([$parent, $other]));          // dua induk: tetap ambigu
        $this->assertNull(I::parentOf([$child, ['id' => 300, 'code' => 'Y', 'parent' => 999]]));

        $this->assertSame('Sinergi Mitra Lestari Indonesia', I::stripParentMarker('Sinergi Mitra Lestari Indonesia (Induk)'));
        $this->assertSame('PT A (Persero)', I::stripParentMarker('PT A (Persero)'));
    }

    public function test_fingerprint_ignores_case_and_spacing_but_not_values(): void
    {
        $a = ['name' => 'Project  A', 'revenue' => 100.0];
        $this->assertSame(I::fingerprint($a), I::fingerprint(['name' => 'project a', 'revenue' => 100]));
        $this->assertNotSame(I::fingerprint($a), I::fingerprint(['name' => 'Project A', 'revenue' => 101]));
    }
}

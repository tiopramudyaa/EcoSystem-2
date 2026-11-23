<?php

namespace Tests\Unit;

use App\Services\MentionTextGuard;
use PHPUnit\Framework\TestCase;

class MentionTextGuardTest extends TestCase
{
    private const NAMES = [
        11 => 'Muhamad Rizqi Alhuda',
        12 => 'M Nurrijal Fathurrohman',
        13 => 'Rizqi Akbar Romadhoni',
    ];

    public function test_stale_mention_whose_chip_was_deleted_is_dropped(): void
    {
        // Kasus tiket #26110345: id Rizqi Akbar Romadhoni ikut terkirim padahal chip-nya sudah dihapus.
        $html = '<p>Dear team <strong style="color: rgb(29, 78, 216);">@Muhamad Rizqi Alhuda</strong>&nbsp;'
              . '<strong style="color: rgb(29, 78, 216);">@M Nurrijal Fathurrohman</strong> berikut terdapat nego mandays</p>';

        [$emp, $role] = MentionTextGuard::filterByNames($html, self::NAMES, []);

        $this->assertSame([11, 12], $emp);
        $this->assertSame([], $role);
    }

    public function test_prefix_name_is_not_satisfied_by_longer_name(): void
    {
        $names = [1 => 'Ahmad', 2 => 'Ahmad Fauzi', 3 => 'Ahmadi'];

        [$emp] = MentionTextGuard::filterByNames('<p>@Ahmad Fauzi tolong cek</p>', $names, []);
        $this->assertSame([2], $emp);

        [$emp] = MentionTextGuard::filterByNames('<p>@Ahmadi dan @Ahmad Fauzi</p>', $names, []);
        $this->assertSame([2, 3], $emp);

        [$emp] = MentionTextGuard::filterByNames('<p>@Ahmad Fauzi lalu @Ahmad juga</p>', $names, []);
        $this->assertSame([1, 2], $emp);
    }

    public function test_role_mentions_are_checked_too(): void
    {
        $roles = [5 => 'Delivery Support User', 6 => 'Delivery Support Head'];

        [$emp, $role] = MentionTextGuard::filterByNames(
            '<p>@delivery support head mohon dibantu</p>',
            [],
            $roles
        );

        $this->assertSame([], $emp);
        $this->assertSame([6], $role);
    }

    public function test_plain_text_and_whitespace_variants_match(): void
    {
        [$emp] = MentionTextGuard::filterByNames("@Muhamad  Rizqi\u{00A0}Alhuda tolong cek", self::NAMES, []);
        $this->assertSame([11], $emp);
    }

    public function test_no_mention_in_text_keeps_nothing(): void
    {
        [$emp, $role] = MentionTextGuard::filterByNames('<p>Tanpa mention</p>', self::NAMES, [5 => 'Delivery Support User']);
        $this->assertSame([], $emp);
        $this->assertSame([], $role);
    }
}

<?php

namespace Tests\Unit;

use App\Models\AppConfig;
use App\Support\MicrosoftIdToken;
use App\Support\MicrosoftLoginSettings;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Login dengan Microsoft: pemeriksaan klaim ID token dan penyimpanan pengaturan
 * (client secret harus terenkripsi dan tidak pernah terbaca di kolom).
 */
class MicrosoftLoginTest extends TestCase
{
    private const TENANT = 'cbcc85ec-3c81-467c-9f7a-fda77b075c13';
    private const CLIENT = '13ef9dfd-75ba-4370-a41e-0c8de62ba7cd';
    private const OID    = '0f1e2d3c-4b5a-6978-8a9b-0c1d2e3f4a5b';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('app_configs');
        Schema::create('app_configs', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->longText('value')->nullable();
            $t->string('description')->nullable();
            $t->timestamps();
        });

        config([
            'services.microsoft_graph.tenant_id'     => '',
            'services.microsoft_graph.client_id'     => '',
            'services.microsoft_graph.client_secret' => '',
        ]);
    }

    private function jwt(array $override = [], int $now = 1_800_000_000): string
    {
        $claims = array_merge([
            'iss'   => 'https://login.microsoftonline.com/' . self::TENANT . '/v2.0',
            'aud'   => self::CLIENT,
            'tid'   => self::TENANT,
            'oid'   => self::OID,
            'nonce' => 'nonce-123',
            'exp'   => $now + 3600,
            'email' => 'Someone@Eclectic.co.id',
        ], $override);

        $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');

        return $b64('{"alg":"RS256"}') . '.' . $b64(json_encode($claims)) . '.sig';
    }

    private function claims(string $jwt, string $nonce = 'nonce-123', int $now = 1_800_000_000): array
    {
        return MicrosoftIdToken::claims($jwt, self::TENANT, self::CLIENT, $nonce, $now);
    }

    public function test_valid_token_returns_claims(): void
    {
        $claims = $this->claims($this->jwt());

        $this->assertSame(self::OID, $claims['oid']);
        $this->assertSame('Someone@Eclectic.co.id', $claims['email']);
    }

    #[DataProvider('badTokens')]
    public function test_invalid_tokens_are_rejected(array $override, string $nonce = 'nonce-123'): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->claims($this->jwt($override), $nonce);
    }

    public static function badTokens(): array
    {
        return [
            'other tenant'   => [['tid' => '11111111-1111-1111-1111-111111111111']],
            'wrong issuer'   => [['iss' => 'https://evil.example/v2.0']],
            'wrong audience' => [['aud' => '22222222-2222-2222-2222-222222222222']],
            'expired'        => [['exp' => 1_800_000_000 - 3600]],
            'nonce mismatch' => [[], 'another-nonce'],
            'missing oid'    => [['oid' => '']],
            'oid not a guid' => [['oid' => 'abc']],
        ];
    }

    public function test_malformed_tokens_are_rejected(): void
    {
        foreach (['', 'a.b', 'a.b.c.d', 'x.!!!.z'] as $bad) {
            try {
                $this->claims($bad);
                $this->fail("Accepted malformed token: {$bad}");
            } catch (InvalidArgumentException $e) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_login_is_off_by_default(): void
    {
        $this->assertFalse(MicrosoftLoginSettings::enabled());
        $this->assertFalse(MicrosoftLoginSettings::loginAvailable());
    }

    public function test_secret_is_encrypted_at_rest_and_round_trips(): void
    {
        MicrosoftLoginSettings::save(true, self::TENANT, self::CLIENT, 'super-secret-value');

        $raw = AppConfig::where('key', MicrosoftLoginSettings::KEY)->value('value');
        $this->assertStringNotContainsString('super-secret-value', $raw);

        $this->assertSame('super-secret-value', MicrosoftLoginSettings::clientSecret());
        $this->assertTrue(MicrosoftLoginSettings::hasStoredSecret());
        $this->assertTrue(MicrosoftLoginSettings::loginAvailable());
    }

    public function test_blank_secret_keeps_the_stored_one(): void
    {
        MicrosoftLoginSettings::save(true, self::TENANT, self::CLIENT, 'keep-me');
        MicrosoftLoginSettings::save(true, self::TENANT, self::CLIENT, '');

        $this->assertSame('keep-me', MicrosoftLoginSettings::clientSecret());
    }

    public function test_enabled_without_complete_credentials_is_not_available(): void
    {
        MicrosoftLoginSettings::save(true, self::TENANT, self::CLIENT, null);

        $this->assertTrue(MicrosoftLoginSettings::enabled());
        $this->assertFalse(MicrosoftLoginSettings::loginAvailable());
    }

    public function test_complete_credentials_but_disabled_is_not_available(): void
    {
        MicrosoftLoginSettings::save(false, self::TENANT, self::CLIENT, 'secret');

        $this->assertFalse(MicrosoftLoginSettings::loginAvailable());
    }

    public function test_empty_fields_fall_back_to_the_graph_app_registration(): void
    {
        config([
            'services.microsoft_graph.tenant_id'     => self::TENANT,
            'services.microsoft_graph.client_id'     => self::CLIENT,
            'services.microsoft_graph.client_secret' => 'env-secret',
        ]);

        MicrosoftLoginSettings::save(true, '', '', null);

        $this->assertSame(self::TENANT, MicrosoftLoginSettings::tenantId());
        $this->assertSame('env-secret', MicrosoftLoginSettings::clientSecret());
        $this->assertTrue(MicrosoftLoginSettings::loginAvailable());
        $this->assertFalse(MicrosoftLoginSettings::hasStoredSecret());
    }

    public function test_undecryptable_secret_is_treated_as_missing(): void
    {
        AppConfig::setJson(MicrosoftLoginSettings::KEY, [
            'enabled' => true, 'tenant_id' => self::TENANT, 'client_id' => self::CLIENT,
            'client_secret' => 'not-a-valid-ciphertext',
        ]);

        $this->assertSame('', MicrosoftLoginSettings::clientSecret());
        $this->assertFalse(MicrosoftLoginSettings::loginAvailable());
    }

    public function test_guid_validation(): void
    {
        $this->assertTrue(MicrosoftLoginSettings::isValidGuid(self::TENANT));
        $this->assertFalse(MicrosoftLoginSettings::isValidGuid('not-a-guid'));
        $this->assertFalse(MicrosoftLoginSettings::isValidGuid(''));
    }
}

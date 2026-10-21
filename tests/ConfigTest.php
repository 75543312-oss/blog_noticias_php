<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias de las funciones de config.php (UT-01 a UT-25).
 */
final class ConfigTest extends TestCase
{
    private const VALIDA = [
        'title' => 'Título', 'summary' => 'Resumen', 'content' => 'Texto',
        'image_url' => '', 'image_alt' => '',
    ];

    protected function setUp(): void
    {
        $_SESSION = [];
    }

    private function con(array $cambios): array
    {
        return validate_news(array_merge(self::VALIDA, $cambios));
    }

    public function testUT01EscapaHtml(): void
    {
        $this->assertSame('&lt;b&gt;&quot;x&quot;&lt;/b&gt;', e('<b>"x"</b>'));
    }

    public function testUT02AceptaNull(): void
    {
        $this->assertSame('', e(null));
    }

    public function testUT03LikeEscape(): void
    {
        $this->assertSame('50\\%\\_a', like_escape('50%_a'));
    }

    public function testUT04TotalPagesRedondeaArriba(): void
    {
        $this->assertSame(2, total_pages(10, 9));
    }

    public function testUT05TotalPagesMinimoUno(): void
    {
        $this->assertSame(1, total_pages(0, 9));
    }

    public function testUT06TotalPagesExacto(): void
    {
        $this->assertSame(1, total_pages(9, 9));
    }

    public function testUT07CsrfTokenEstable(): void
    {
        $token = csrf_token();
        $this->assertSame($token, csrf_token());
        $this->assertSame(64, strlen($token));
    }

    public function testUT08NoticiaValida(): void
    {
        $this->assertSame([], $this->con([]));
    }

    public function testUT09CamposVacios(): void
    {
        $errores = $this->con(['title' => '', 'summary' => '', 'content' => '']);
        $this->assertSame(['title', 'summary', 'content'], array_keys($errores));
    }

    public function testUT10Titulo180Valido(): void
    {
        $this->assertArrayNotHasKey('title', $this->con(['title' => str_repeat('á', 180)]));
    }

    public function testUT11Titulo181Invalido(): void
    {
        $this->assertArrayHasKey('title', $this->con(['title' => str_repeat('á', 181)]));
    }

    public function testUT12Resumen300Valido(): void
    {
        $this->assertArrayNotHasKey('summary', $this->con(['summary' => str_repeat('a', 300)]));
    }

    public function testUT13Resumen301Invalido(): void
    {
        $this->assertArrayHasKey('summary', $this->con(['summary' => str_repeat('a', 301)]));
    }

    public function testUT14ImagenSinAlt(): void
    {
        $this->assertArrayHasKey('image_alt', $this->con(['image_url' => 'https://x.pe/a.jpg']));
    }

    public function testUT15Alt200Valido(): void
    {
        $this->assertSame([], $this->con(['image_url' => 'https://x.pe/a.jpg', 'image_alt' => str_repeat('a', 200)]));
    }

    public function testUT16Alt201Invalido(): void
    {
        $this->assertArrayHasKey('image_alt', $this->con(['image_url' => 'https://x.pe/a.jpg', 'image_alt' => str_repeat('a', 201)]));
    }

    public function testUT17UrlJavascriptRechazada(): void
    {
        $this->assertArrayHasKey('image_url', $this->con(['image_url' => 'javascript:alert(1)', 'image_alt' => 'x']));
    }

    public function testUT18UrlNoHttpRechazada(): void
    {
        $this->assertArrayHasKey('image_url', $this->con(['image_url' => 'file://x.pe/a.jpg', 'image_alt' => 'x']));
    }

    public function testUT19Contrasena7Invalida(): void
    {
        $this->assertNotNull(password_problem('Abc1234', 'Abc1234'));
    }

    public function testUT20Contrasena8Valida(): void
    {
        $this->assertNull(password_problem('Abc12345', 'Abc12345'));
    }

    public function testUT21ContrasenaSinNumeros(): void
    {
        $this->assertNotNull(password_problem('Abcdefgh', 'Abcdefgh'));
    }

    public function testUT22ConfirmacionDistinta(): void
    {
        $this->assertNotNull(password_problem('Abc12345', 'Abc12346'));
    }

    public function testUT23CuatroFallosSinBloqueo(): void
    {
        $t = 1000;
        for ($i = 0; $i < 4; $i++) {
            register_login_failure($t);
        }
        $this->assertSame(0, login_locked_seconds($t));
    }

    public function testUT24QuintoFalloBloquea(): void
    {
        $t = 1000;
        for ($i = 0; $i < 5; $i++) {
            register_login_failure($t);
        }
        $this->assertSame(60, login_locked_seconds($t));
    }

    public function testUT25BloqueoExpira(): void
    {
        $t = 1000;
        for ($i = 0; $i < 5; $i++) {
            register_login_failure($t);
        }
        $this->assertSame(0, login_locked_seconds($t + 60));
    }

    public function testResetLoginFailuresLimpiaBloqueo(): void
    {
        $t = 1000;
        for ($i = 0; $i < 5; $i++) {
            register_login_failure($t);
        }
        reset_login_failures();
        $this->assertSame(0, login_locked_seconds($t));
    }

    public function testFlashSeLeeUnaSolaVez(): void
    {
        flash('Hola', 'success');
        $this->assertSame(['message' => 'Hola', 'type' => 'success'], get_flash());
        $this->assertNull(get_flash());
    }

    public function testIsAdmin(): void
    {
        $this->assertFalse(is_admin());
        $_SESSION['admin_id'] = 1;
        $this->assertTrue(is_admin());
    }
}
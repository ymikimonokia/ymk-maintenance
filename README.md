# YMK Maintenance

Modo mantenimiento para WordPress. Muestra una página con HTTP 503 o redirige a una URL externa mientras el sitio está en mantenimiento.

Excluye automáticamente bots de SEO, administradores y la página de login.

## Requirements

- WordPress 5.8+
- PHP 7.4+
- CPT `article` activo (para mostrar contenido de mantenimiento personalizado)

## Installation

1. Upload `ymk-maintenance` to `/wp-content/plugins/`
2. Activate through **Plugins**
3. Go to **Settings → YMK Maintenance** to configure

## Options

| Option | Description |
|--------|-------------|
| Activar modo mantenimiento | Bloquea el acceso a visitantes |
| Slug del artículo | Slug de un post del CPT `article` a mostrar como página de mantenimiento |
| URL de redirección | Si se indica, redirige aquí en lugar de mostrar el artículo |

## Behavior

- Admins (`edit_others_pages`), bots conocidos y `wp-login.php` quedan siempre excluidos
- Si hay URL configurada → redirect 302
- Si hay slug → renderiza el artículo con cabecera HTTP 503
- Si no hay ni URL ni slug → muestra mensaje genérico con nombre del sitio

## Changelog

See [CHANGELOG.md](CHANGELOG.md)

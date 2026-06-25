# habitosapp

Aplicacion de habitos para Metodologia de Pruebas.

## Backend Laravel

Base inicial de API y base de datos construida desde `sistema_habitos_especificacion_2md.md`.

### Comandos utiles

```bash
php composer.phar install
php artisan migrate:fresh --seed
php artisan test
php artisan serve
```

### API inicial

- `GET /api/categories`
- `POST /api/categories`
- `GET /api/habits`
- `POST /api/habits`
- `GET /api/habits/{habit}`
- `DELETE /api/habits/{habit}`
- `GET /api/habits/{habit}/statistics`
- `POST /api/habit-tracking`

Las rutas estan protegidas con middleware `auth` para poder testearlas con usuarios autenticados de Laravel. El siguiente paso recomendado es sumar Breeze/Sanctum segun si la interfaz sera Blade o SPA.

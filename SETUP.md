# Configuración de la Base de Datos de Países

## Descripción

Este proyecto utiliza la **REST Countries API v5** para poblar una base de datos con información de países, continentes, monedas y relaciones fronterizas. Los datos se cachean localmente en JSON para garantizar que el seeder funcione incluso si la API cambia o no está disponible.

## Estructura de Datos

### Tablas
- **continents**: 6 continentes (código 2-char, nombre)
- **countries**: ~100 países con detalles (códigos ISO, capital, coordenadas, población, GINI, idiomas)
- **currencies**: ~70 monedas (código ISO 4217, nombre, símbolo)
- **country_borders**: Relaciones N:M reflexivas entre países fronterizos
- **country_currency**: Relaciones N:M entre países y sus monedas oficiales

### Relaciones de Modelos

```
Continent (1) ──→ (N) Country
Country (N) ──→ (M) Currency
Country (N) ──→ (M) Country (vecinos/fronteras)
```

## Instalación Inicial

### 1. Configurar API Key (opcional pero recomendado)

Agrega tu API key en `.env`:

```env
REST_COUNTRIES_API_KEY=rc_live_c5a0963aac56470dbb90dc5c320d1f4c
```

Obtén una gratis en: https://www.restcountries.com/

### 2. Ejecutar Migraciones y Seeder

```bash
php artisan migrate:fresh --seed
```

El seeder cargará los datos en este orden de prioridad:

1. **Cache local JSON** (`database/seeders/countries_api_v5.json`)
2. **API v5** (si existe API key y no hay cache)
3. **Fallback PHP** (datos mínimos para desarrollo offline)

## Actualizar Datos desde la API

Cuando quieras descargar datos frescos de la API y actualizar el cache:

```bash
php artisan app:fetch-countries-data
```

Esto:
- Descargar datos de la API v5
- Cachea los datos en JSON local
- Permite que futuras ejecuciones del seeder usen ese cache sin depender de la API

## Estructura del Proyecto

```
database/
├── migrations/
│   ├── create_continents_table.php
│   ├── create_currencies_table.php
│   ├── create_countries_table.php
│   ├── create_country_borders_table.php
│   └── create_country_currency_table.php
├── factories/
│   ├── ContinentFactory.php
│   ├── CountryFactory.php
│   └── CurrencyFactory.php
├── seeders/
│   ├── CountryDatabaseSeeder.php
│   ├── countries_api_v5.json         ← Cache local (descargado con comando)
│   ├── countries_data.php            ← Fallback mínimo
│   └── DatabaseSeeder.php

app/
├── Models/
│   ├── Continent.php
│   ├── Country.php
│   └── Currency.php
└── Console/Commands/
    └── FetchCountriesData.php        ← Comando para descargar y cachear
```

## Flujo de Resiliencia

```
Seeder Intenta Cargar Datos
    ↓
¿Existe countries_api_v5.json?
    ├─ SÍ  → Usa cache (rápido, sin conexión)
    └─ NO  → ¿Hay API key configurada?
            ├─ SÍ  → Descarga API + cachea resultado
            └─ NO  → Usa fallback PHP (15 países)
```

Este diseño garantiza:
- ✅ **Velocidad**: Cache JSON para CI/CD sin latencia
- ✅ **Resiliencia**: Funciona sin API key o si la API cambia
- ✅ **Mantenibilidad**: Actualizar datos es explícito, no mágico
- ✅ **Educativo**: Demuestra patrones de caching y fallbacks

## Comandos Útiles

```bash
# Ejecutar migraciones + seed
php artisan migrate:fresh --seed

# Descargar y cachear datos de la API
php artisan app:fetch-countries-data

# Consultar datos via Tinker
php artisan tinker
> App\Models\Country::where('cca3', 'ESP')->first()
> App\Models\Country::with('neighbors', 'currencies')->find(1)

# Consultar via SQLite
sqlite3 database/database.sqlite "SELECT name, capital FROM countries LIMIT 10;"
```

## Notas Didácticas

Este proyecto ejemplifica:

1. **Modelos Eloquent** con relaciones complejas (BelongsTo, BelongsToMany reflexivas)
2. **Factories** para generar datos de prueba
3. **Seeders** con transacciones y manejo robusto de errores
4. **Migraciones** con constraints y índices estratégicos
5. **Cachés locales** para resiliencia ante cambios externos
6. **Comandos Artisan** personalizados
7. **JSON casting** en atributos de modelo
8. **Relaciones N:M** simples y reflexivas

## Solución de Problemas

### "Array to string conversion"
Si ocurre este error durante el seed, probablemente hay un campo que espera un string pero recibe un array. Verifica en `normalizeCountryData()` que todos los campos se conviertan correctamente a tipos escalares.

### "API key inválida"
- Verifica que `REST_COUNTRIES_API_KEY` está en `.env`
- La API usa Bearer tokens, no Basic auth
- Si es demo key: `rc_live_demo` (limitado a 25 países)

### "No hay datos disponibles"
1. Verifica `countries_api_v5.json` existe en `database/seeders/`
2. Si no existe, ejecuta `php artisan app:fetch-countries-data`
3. Si falla, revisa que tengas API key y conexión a internet

## Referencias

- [REST Countries API](https://www.restcountries.com/)
- [Laravel Eloquent Relations](https://laravel.com/docs/eloquent-relationships)
- [Laravel Seeders](https://laravel.com/docs/seeding)
- [Artisan Console](https://laravel.com/docs/artisan)

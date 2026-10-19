# 🌍 Countries Info - REST API Didáctica

Un proyecto educativo en **Laravel 12** que demuestra cómo construir una API robusta para información de países, continentes, monedas y relaciones fronterizas usando **datos cacheados de REST Countries API v5**.

## 🎯 Objetivos Didácticos

Este proyecto enseña:

- ✅ **Modelos Eloquent avanzados**: Relaciones BelongsTo, BelongsToMany reflexivas, factory patterns
- ✅ **Migraciones robustas**: Constraints, índices estratégicos, relaciones N:M
- ✅ **Seeders con resiliencia**: Caché local, fallbacks, transacciones, manejo de errores
- ✅ **Comandos Artisan personalizados**: Para actualizar datos sin dependencias de APIs externas
- ✅ **Patrones de arquitectura**: Normalización de datos, casting de atributos, JSON en BD
- ✅ **API resources**: Transformación y versionado de respuestas (próximas fases)

## 📦 Estructura de Datos

### Base de Datos

```sql
continents (6)          -- Continentes mundiales (código, nombre)
├─ countries (99)       -- Países con detalles (ISO, capital, coordenadas, población, GINI, idiomas)
│  ├─ country_currency (N:M)  -- Monedas oficiales de cada país
│  └─ country_borders (N:M reflexiva)  -- Frontera entre países
└─ currencies (70)      -- Monedas ISO 4217
```

### Relaciones de Modelos

```php
// Uno a Muchos
Continent -> hasMany(Country)
Country -> belongsTo(Continent)

// Muchos a Muchos
Country -> belongsToMany(Currency, 'country_currency')
Currency -> belongsToMany(Country, 'country_currency')

// Reflexiva (N:M)
Country -> belongsToMany(Country, 'country_borders', 'country_id', 'neighbor_id')
```

## 🚀 Inicio Rápido

### 1. Clonar y Configurar

```bash
git clone <repo>
cd contries-info
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Ejecutar Migraciones + Seeder

```bash
php artisan migrate:fresh --seed
```

Esto:
- Crea la estructura de BD
- Carga 99 países desde cache local JSON
- Vincula monedas y fronteras automáticamente

### 3. Verificar Datos

```bash
sqlite3 database/database.sqlite "SELECT COUNT(*) as total FROM countries;"
# Output: 99

php artisan tinker
> App\Models\Country::first()
```

## 🔄 Resiliencia: Caché de Datos

El proyecto implementa un sistema de **3 niveles de fallback** para garantizar que el seeder nunca dependa de APIs externas:

### Jerarquía de Carga

```
1️⃣  Cache JSON Local (database/seeders/countries_api_v5.json)
    └─ Rápido, sin conexión, actualizable manualmente
    
2️⃣  API v5 (si no hay cache + API key disponible)
    └─ Descarga automática, se cachea para futuras ejecuciones
    
3️⃣  Fallback PHP (database/seeders/countries_data.php)
    └─ 15 países codeados, para desarrollo offline garantizado
```

### Actualizar Cache desde API

```bash
php artisan app:fetch-countries-data
```

Este comando:
- ✅ Descarga datos frescos de REST Countries API v5
- ✅ Guarda en `database/seeders/countries_api_v5.json`
- ✅ El seeder futuro lo usará automáticamente (sin API key)
- ✅ Ideal para CI/CD, desarrollo offline, y resistencia a cambios de API

## 📁 Estructura del Proyecto

```
contries-info/
├── app/
│   ├── Models/
│   │   ├── Continent.php
│   │   ├── Country.php
│   │   └── Currency.php
│   └── Console/Commands/
│       └── FetchCountriesData.php      ← Descarga y cachea API
│
├── database/
│   ├── migrations/                     ← 5 migraciones
│   ├── factories/                      ← 3 factories para tests
│   ├── seeders/
│   │   ├── CountryDatabaseSeeder.php   ← 2 fases: países + fronteras
│   │   ├── countries_api_v5.json       ← Cache automático
│   │   ├── countries_data.php          ← Fallback mínimo
│   │   └── DatabaseSeeder.php
│
├── SETUP.md                            ← Guía detallada de configuración
└── README.md                           ← Este archivo
```

## 🔍 Ejemplos de Uso

### Consultar Países

```php
// Obtener todos los países de Europa
$europeanCountries = Country::whereHas('continent', fn($q) => 
    $q->where('code', 'EU')
)->get();

// País con sus relaciones
$spain = Country::with('continent', 'currencies', 'neighbors')
    ->where('cca3', 'ESP')
    ->first();

echo $spain->name;                              // "Spain"
echo $spain->capital;                           // "Madrid"
echo $spain->continent->name;                   // "Europa"
echo $spain->currencies->count();               // 1 (EUR)
echo $spain->neighbors->pluck('name');          // ["France", "Portugal", "Andorra"]
```

### Via SQL

```bash
sqlite3 database/database.sqlite

-- Países más poblados
SELECT name, population FROM countries ORDER BY population DESC LIMIT 5;

-- Fronteras de un país
SELECT c.name FROM countries c
INNER JOIN country_borders cb ON c.id = cb.neighbor_id
WHERE cb.country_id = (SELECT id FROM countries WHERE cca3 = 'ESP');

-- Monedas de América
SELECT DISTINCT cu.name FROM currencies cu
INNER JOIN country_currency cc ON cu.id = cc.currency_id
INNER JOIN countries c ON cc.country_id = c.id
INNER JOIN continents cont ON c.continent_id = cont.id
WHERE cont.code = 'AM'
ORDER BY cu.name;
```

## 🛠️ Desarrollo

### Ejecutar Tests (Próxima Fase)

```bash
php artisan test
```

### Generar Factorías (para tests)

```bash
php artisan make:factory CountryFactory --model=Country
```

### Verificar Rutas (próximas)

```bash
php artisan route:list
```

## 📚 Conceptos Clave Ilustrados

### 1. Relaciones Reflexivas (N:M)

```php
// Country.php
public function neighbors(): BelongsToMany
{
    return $this->belongsToMany(
        Country::class,
        'country_borders',
        'country_id',
        'neighbor_id'
    );
}
// Uso: $country->neighbors()->get();
```

### 2. Casting de Atributos

```php
protected $casts = [
    'languages' => 'json',           // Almacena array PHP como JSON
    'latitude' => 'decimal:7',       // 7 decimales de precisión
    'longitude' => 'decimal:7',
    'gini' => 'decimal:1',
];
```

### 3. Normalización de Datos (multiple sources)

El seeder maneja 3 formatos de API diferentes:
- REST Countries API v3.1 (estructura antigua)
- REST Countries API v5 (estructura actual, cacheada)
- Fallback PHP local (datos mínimos)

Esto demuestra **adaptability** crucial en sistemas reales.

### 4. Transacciones y Resiliencia

```php
DB::transaction(function () {
    // Fase 1: Crear países
    // Fase 2: Conectar fronteras
    // Si algo falla: rollback automático
});
```

## 🔐 Variables de Entorno

```env
# .env
REST_COUNTRIES_API_KEY=rc_live_c5a0963aac56470dbb90dc5c320d1f4c  # Opcional
# La clave sale de: https://www.restcountries.com/ (registro gratuito)
```

⚠️ **IMPORTANTE**: `.env` está en `.gitignore`, tu API key nunca se committea.

## 🌐 API REST Countries v5

- **Endpoint**: `https://api.restcountries.com/countries/v5`
- **Docs**: https://www.restcountries.com/
- **Límites**: Free tier = 100 países/request
- **Auth**: Bearer token (obtenible gratis)

## 📖 Ver También

- [SETUP.md](./SETUP.md) - Guía detallada de configuración
- [Laravel Eloquent Relations](https://laravel.com/docs/eloquent-relationships)
- [Laravel Seeders](https://laravel.com/docs/seeding)
- [REST Countries API](https://www.restcountries.com/)

## 📝 Licencia

MIT - Libre para uso educativo y comercial.

---

**Hecho con ❤️ para aprender Laravel**

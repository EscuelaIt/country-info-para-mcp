# 🏢 ACME LTDA - Datos de Negocio

Este documento explica en detalle los datos de negocio ficticio de la empresa **ACME LTDA** integrados en el servidor MCP.

## 📌 ¿Por Qué ACME LTDA?

La información pública de países (población, capital, monedas, etc.) está disponible en múltiples fuentes y un modelo LLM puede acceder a ella. Sin embargo, datos internos de una empresa como ACME LTDA **no son públicos** y representan un caso de uso realista para un servidor MCP:

- ✅ El modelo LLM **NO** puede saber datos de ACME
- ✅ Solo a través del MCP puede consultarlos
- ✅ Simula un acceso a sistemas empresariales internos

## 📊 Datos Incluidos

Cada país en la base de datos tiene tres campos ACME:

| Campo | Tipo | Rango | Descripción |
|-------|------|-------|------------|
| `acme_branches` | INT | 0-85 | Número de sucursales operativas |
| `acme_volume_usd` | BIGINT | 0-47M | Volumen anual de negocio en USD |
| `acme_customers` | INT | 0-7.4k | Clientes activos en el país |

## 🎯 Lógica de Distribución

### Fase 1: Selección de Países

ACME opera **solo en los 100 países más poblados del mundo**.

```php
// Pseudocódigo
$countries = Country::orderByDesc('population')->take(100)->get();
```

Países por debajo del 100º tienen `acme_branches = 0`.

### Fase 2: Número de Sucursales

**Base**: 1 sucursal por país (mínimo para estar en la red ACME)

**Adicional**: Basado en población excedente y continente

```
población_extra = población_país - población_100º_país

// Cada 50 millones de habitantes adicionales:
if (continente == "Europa") {
    sucursales_adicionales = random(3, 10) * bloques_de_50M
} else {
    sucursales_adicionales = random(1, 3) * bloques_de_50M
}

sucursales_totales = 1 + sucursales_adicionales
```

**Ejemplo (India)**:
- Población: 1,429M
- Población 100º: 9M
- Extra: 1,420M
- Bloques: floor(1,420M / 50M) = 28 bloques
- Extra aleatoria por bloque: ~3 (media)
- **Total: 1 + (28 × 3) ≈ 85 sucursales** ✓

**Ejemplo (España)**:
- Población: 47M
- Población 100º: 9M  
- Extra: 38M
- Bloques: floor(38M / 50M) = 0 bloques
- **Total: 1 + 0 = 1 sucursal** ✓

**Por qué Europa tiene más?** Mercado más maduro y competitivo.

### Fase 3: Volumen de Negocio

Cada sucursal genera un volumen **independiente** e **aleatorio**:

```
volumen_por_sucursal = random($100k, $1M) USD
volumen_total = volumen_por_sucursal × sucursales
```

**Rango resultado**: $0.1M (1 sucursal × $100k) a ~$47M (85 sucursales × $1M)

### Fase 4: Número de Clientes

Depende del **continente** (mercados asiáticos tienen clientes de mayor valor):

```
if (continente == "Asia") {
    clientes_por_sucursal = random(50, 100)
} else {
    clientes_por_sucursal = random(30, 60)
}

clientes_totales = clientes_por_sucursal × sucursales
```

**Ejemplo Asia (India, 85 sucursales)**:
- 85 sucursales × 75 clientes/promedio = ~6,400 clientes

**Ejemplo Europa (España, 1 sucursal)**:
- 1 sucursal × 45 clientes/promedio = ~45 clientes

## 📈 Distribución Resultante

### Top 10 Países por Volumen de Negocio

| País | Sucursales | Volumen USD | Clientes |
|------|-----------|------------|----------|
| 🇮🇳 India | 85 | $17.3M | 5,865 |
| 🇨🇳 China | 82 | $47.8M | 7,462 |
| 🇺🇸 USA | 19 | $10.5M | 988 |
| 🇵🇰 Pakistan | 13 | $1.6M | 871 |
| 🇧🇩 Bangladesh | 10 | $2.5M | 740 |
| 🇩🇪 Germany | 10 | $4.6M | 300 |
| 🇬🇧 UK | 10 | $9.5M | 600 |
| 🇷🇺 Russia | 7 | $5.1M | 224 |
| 🇫🇷 France | 6 | $1.5M | 330 |
| 🇮🇩 Indonesia | 6 | $4.7M | 372 |

### Estadísticas Globales

```
Total países operacionales:  100
Total sucursales:             ~1,200
Volumen global:               ~$250M USD
Clientes globales:            ~80,000
```

## 🔍 Consultar Datos ACME

### Via PHP/Eloquent

```php
// Obtener país con datos ACME
$india = Country::where('name', 'India')->first();
echo $india->acme_branches;      // 85
echo $india->acme_volume_usd;    // 17303365
echo $india->acme_customers;     // 5865

// Ordenar por volumen
$topCountries = Country::where('acme_branches', '>', 0)
    ->orderByDesc('acme_volume_usd')
    ->limit(10)
    ->get();

// Filtrar por región de negocio (Asia)
$asiaOperations = Country::whereHas('continent', fn($q) => 
    $q->where('code', 'AS')
)->where('acme_branches', '>', 0)
->sum('acme_volume_usd');
```

### Via MCP (Recomendado)

El tool `CountryListTool` retorna automáticamente datos de ACME:

**Solicitud:**
```json
{
  "tool": "CountryListTool",
  "arguments": {
    "name": "India"
  }
}
```

**Respuesta:**
```json
{
  "count": 1,
  "countries": [
    {
      "name": "India",
      "official_name": "Republic of India",
      "population": 1429404000,
      "cca3": "IND",
      "acme": {
        "branches": 85,
        "volume_usd": 17303365,
        "customers": 5865
      }
    }
  ]
}
```

### Via SQL

```bash
sqlite3 database/database.sqlite

-- Top 10 países por volumen ACME
SELECT name, acme_branches, acme_volume_usd, acme_customers
FROM countries
WHERE acme_branches > 0
ORDER BY acme_volume_usd DESC
LIMIT 10;

-- Volumen total por continente
SELECT cont.name, COUNT(*) as countries,
       SUM(c.acme_branches) as total_branches,
       SUM(c.acme_volume_usd) as total_volume_usd
FROM countries c
INNER JOIN continents cont ON c.continent_id = cont.id
WHERE c.acme_branches > 0
GROUP BY c.continent_id
ORDER BY total_volume_usd DESC;

-- Países sin operaciones ACME (no en top 100)
SELECT name, population FROM countries
WHERE acme_branches = 0
ORDER BY population DESC;
```

## 🔄 Regenerar Datos ACME

Si necesitas regenerar los datos con nuevos valores aleatorios:

```bash
# Reset de la tabla
php artisan migrate:rollback --step=1
php artisan migrate

# Reseed solo ACME
php artisan db:seed --class=AcmeDataSeeder
```

## 🎓 Concepto Didáctico

### Problema: ¿Por qué un MCP para ACME?

**Sin MCP:**
```
Usuario (LLM) → "¿Cuántas sucursales tiene ACME en India?"
Respuesta: "No tengo acceso a datos internos de ACME"
❌ Inútil
```

**Con MCP (este proyecto):**
```
Usuario → MCP Tool: CountryListTool("India")
← MCP retorna datos públicos + datos ACME
Usuario → "India tiene 85 sucursales de ACME"
✅ Útil y realista
```

### Caso de Uso Real

Imagina un chatbot corporativo:
- 📊 Consultar mercados donde ACME está presente
- 💰 Analizar rentabilidad por región
- 🎯 Tomar decisiones de expansión
- 📈 Comparar desempeño con competidores (ficticio)

Todo sin que el modelo LLM tenga acceso directo a sistemas internos.

## 📝 Notas de Implementación

### Archivo de Migración
- `database/migrations/2026_10_22_145115_add_acme_data_to_countries_table.php`
- Añade 3 columnas a la tabla `countries`
- Rollbackeable (elimina columnas en down)

### Archivo de Seeder
- `database/seeders/AcmeDataSeeder.php`
- Ejecuta lógica de distribución
- Muestra progreso con mensajes informativos
- Tarda ~100ms para 100 países

### Integración MCP
- `app/Mcp/Tools/CountryListTool.php`
- Incluye datos de ACME en array `acme` (no nullable)
- Schema actualizado con campos documentados
- Disponible automáticamente en respuestas

---

**Hecho para demostrar la utilidad real de servidores MCP en acceso a datos corporativos internos.**

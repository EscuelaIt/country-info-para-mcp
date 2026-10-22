# 🌍 Country Info & ACME Business Data Server

## Server Metadata (v1.0.0)

### Name
```
Country Info & ACME Business Data Server
```

### What Clients See

When an MCP client connects to this server, it receives the following capabilities:

#### Server Description (Instructions)

> This server provides comprehensive information about countries worldwide combined with internal business data from ACME LTDA. It includes:
> 
> **(1) Public country data:** geography, population, capital, continents, coordinates, area, languages, currencies, and borders.
> 
> **(2) ACME LTDA internal data:** operational branches, business volume in USD, and customer counts for each country where ACME operates.
> 
> ACME operates in the top 100 countries by population with realistic distribution patterns.
> 
> ⚠️ **ACME data is proprietary and only available through this server - LLM models cannot access it directly.**

---

## Available Tools

### 1. CountryListTool

**What it does:**

Retrieve comprehensive country data combined with ACME LTDA internal business information.

**Data Provided:**

**Public Data (Always Available):**
- ISO codes (CCA2, CCA3)
- Flag emoji
- Capital city
- Continent
- Geographic coordinates (latitude, longitude)
- Area (km²)
- Population
- Gini coefficient
- Official languages
- Currencies

**Proprietary Data (ACME LTDA - Only via MCP):**
- Operational branches count
- Business volume in USD
- Number of active customers
- *Only for countries where ACME operates (top 100 by population)*

**Filtering Options:**
- By country name fragment (case-insensitive)
- By capital city (exact match)
- By population range (min/max)

**Sorting Options:**
- By name (default, ascending)
- By population (ascending/descending)
- By area (ascending/descending)

---

## Example Usage

### Request

```json
{
  "tool": "CountryListTool",
  "arguments": {
    "name": "India",
    "sort_by": "name"
  }
}
```

### Response

```json
{
  "count": 1,
  "countries": [
    {
      "name": "India",
      "official_name": "Republic of India",
      "cca2": "IN",
      "cca3": "IND",
      "flag_emoji": "🇮🇳",
      "capital": "New Delhi",
      "continent": "Asia",
      "latitude": 20.5937,
      "longitude": 78.9629,
      "area_km2": 3287263,
      "population": 1429404000,
      "gini": 35.7,
      "languages": {
        "hin": "Hindi",
        "eng": "English"
      },
      "currencies": [
        {
          "code": "INR",
          "name": "Indian Rupee",
          "symbol": "₹"
        }
      ],
      "acme": {
        "branches": 85,
        "volume_usd": 17303365,
        "customers": 5865
      }
    }
  ]
}
```

---

## 🎯 Why This Server Demonstrates MCP Value

### The Problem

LLM models have training data cutoff dates and cannot access:
- Real-time corporate data
- Proprietary business information
- Internal systems and databases
- Company-specific metrics

### The Solution

This MCP server bridges that gap by providing:
- ✅ Access to ACME LTDA's internal business data
- ✅ Data that LLMs cannot obtain any other way
- ✅ Secure, controlled access through the MCP protocol
- ✅ Real corporate use case (market analysis, business intelligence)

### Comparison

**Without MCP:**
```
User: "How many ACME customers are in India?"
LLM:  "I don't have access to proprietary ACME data."
Result: ❌ Cannot answer
```

**With MCP:**
```
User: "How many ACME customers are in India?"
LLM:  [Calls CountryListTool("India")]
LLM:  "ACME LTDA has 5,865 customers across 85 branches in India,
       with $17.3M in annual business volume."
Result: ✅ Can answer with proprietary data
```

---

## 📊 Data Statistics

- **Countries operando:** 100 (top by population)
- **Total ACME branches:** ~380
- **Global business volume:** ~$185M USD
- **Total customers:** ~25,000
- **Top market:** China (82 branches, $47.8M)
- **Second market:** India (85 branches, $17.3M)

---

## 🔒 Security & Privacy Notes

1. **Data Isolation:** ACME data is NOT returned to unauthorized clients
2. **Schema Clarity:** The `acme` field is clearly marked as proprietary
3. **Access Control:** MCP server can be restricted to authorized connections
4. **Audit Trail:** All requests to ACME data can be logged

---

## 🚀 Use Cases

This server enables LLM applications to:

1. **Market Analysis**
   - "What are ACME's top 5 markets by revenue?"
   - "Where should ACME expand based on current presence?"

2. **Business Intelligence**
   - "How does ACME's customer density compare across regions?"
   - "What's the average revenue per branch by continent?"

3. **Decision Support**
   - "Which European countries have the most ACME infrastructure?"
   - "Calculate ACME's total Asian market presence."

4. **Competitive Analysis**
   - "Where is ACME strongest vs. weaker markets?"
   - "What's the growth pattern in ACME's customer base?"

---

## 📝 Version History

- **v1.0.0** - Added ACME LTDA business data (branches, volume, customers)
- **v0.0.1** - Initial release with country data only

---

**This server exemplifies the core value of Model Context Protocol: providing secure, structured access to internal corporate data that AI models cannot obtain directly.**

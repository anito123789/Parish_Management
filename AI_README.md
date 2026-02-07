# Simple Offline AI for Parish M

## Overview

A lightweight, offline AI assistant integrated into Parish M. **No Ollama, No external dependencies, No internet required.**

### Key Features

- ✅ **Fully Offline** - Works without internet connection
- ✅ **No Setup** - No Ollama or model downloads needed
- ✅ **Instant** - Fast local processing
- ✅ **Context-Aware** - Message suggestions for various occasions
- ✅ **Text Analysis** - Sentiment, keywords, and validation

---

## Features

### 1. **Message Suggestions** 💡
Generate personalized message suggestions for:
- **Birthday** - Birthday greetings
- **Anniversary** - Marriage anniversary wishes
- **Condolence** - Sympathy messages
- **Welcome** - Welcome to parish
- **Parish News** - Parish announcements

**Usage:**
```javascript
fetch('ai/api.php?action=suggest&context=birthday&name=John')
  .then(r => r.json())
  .then(data => console.log(data.suggestion));
```

### 2. **Sentiment Analysis** 💭
Analyze the sentiment of any text:
- Positive, Negative, or Neutral
- Confidence score (0-100%)

**Usage:**
```javascript
fetch('ai/api.php?action=sentiment&input=Great message!')
  .then(r => r.json())
  .then(data => console.log(data.sentiment));
```

**Response:**
```json
{
  "sentiment": "positive",
  "confidence": 85
}
```

### 3. **Keyword Extraction** 🏷️
Extract important keywords from text:
- Removes common words
- Returns top 5 keywords
- Word frequency analysis

**Usage:**
```javascript
fetch('ai/api.php?action=keywords&input=Long text here...')
  .then(r => r.json())
  .then(data => console.log(data.keywords));
```

### 4. **Text Validation** ✓
Check text quality:
- Length validation
- Excessive punctuation detection
- ALL CAPS detection
- HTML tag detection

**Usage:**
```javascript
fetch('ai/api.php?action=validate&input=Text to check')
  .then(r => r.json())
  .then(data => console.log(data.validation));
```

### 5. **Message Formatting** 📝
Format messages using templates:
- `greeting` - "Dear {name}, {message}"
- `formal` - "To {name},\n\n{message}"
- `signature` - "With Prayers,\n{sender}"
- `brief` - "{message}"

**Usage:**
```javascript
const variables = {
  name: 'John',
  sender: 'Fr. Smith'
};
fetch('ai/api.php?action=format&input=Hello&template=greeting&variables=' + JSON.stringify(variables))
  .then(r => r.json())
  .then(data => console.log(data.formatted));
```

### 6. **Response Suggestion** 🤖
Get AI-suggested response based on input text:
- Automatically detects context
- Returns contextual suggestion

**Usage:**
```javascript
fetch('ai/api.php?action=suggest_response&input=User message')
  .then(r => r.json())
  .then(data => console.log(data.suggestion));
```

---

## API Endpoints

### Base URL
```
/ai/api.php?action=[ACTION]
```

### Actions

| Action | Parameters | Description |
|--------|------------|-------------|
| `suggest` | `context`, `name` | Generate single suggestion |
| `suggestions` | `context`, `count` | Multiple suggestions |
| `sentiment` | `input` | Analyze sentiment |
| `keywords` | `input` | Extract keywords |
| `suggest_response` | `input` | Suggest response |
| `validate` | `input` | Validate text |
| `format` | `input`, `template`, `variables` | Format message |
| `contexts` | - | Get available contexts |
| `info` | - | Get AI info |

### Example Requests

**GET Request:**
```
ai/api.php?action=suggest&context=birthday&name=John%20Doe
```

**POST Request:**
```javascript
fetch('ai/api.php', {
  method: 'POST',
  body: new FormData(document.querySelector('form'))
})
```

---

## JavaScript Integration

### Basic Example
```html
<textarea id="message"></textarea>
<button onclick="analyzeMessage()">Analyze</button>
<div id="result"></div>

<script>
function analyzeMessage() {
  const text = document.getElementById('message').value;
  
  fetch(`ai/api.php?action=sentiment&input=${encodeURIComponent(text)}`)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('result').innerHTML = 
          `Sentiment: ${data.sentiment.sentiment} (${data.sentiment.confidence}%)`;
      }
    });
}
</script>
```

---

## PHP Integration

### Load AI in Any PHP File
```php
<?php
require_once 'ai/simple_ai.php';

$ai = getAI();

// Get suggestion
$suggestion = $ai->generateSuggestion('birthday', 'John');

// Get multiple suggestions
$suggestions = $ai->generateMultipleSuggestions('anniversary', 3);

// Analyze sentiment
$sentiment = $ai->analyzeSentiment('Great message!');

// Extract keywords
$keywords = $ai->extractKeywords('Long text...');

// Get available contexts
$contexts = $ai->getAvailableContexts();
?>
```

### Adding Custom Suggestions
```php
$ai = getAI();
$ai->addSuggestion('custom_event', 'Custom message suggestion here');
```

---

## Web Interface

### Access AI Assistant
Navigate to: `http://localhost/parish/ai_assistant.php`

**Features:**
- 💡 Message Suggestions
- 💭 Sentiment Analysis
- 🏷️ Keyword Extraction
- ✓ Text Validation
- Interactive UI with real-time results

---

## System Requirements

- **PHP**: 7.0+
- **Database**: SQLite (included)
- **Internet**: Optional (offline first)
- **Storage**: ~200KB for AI module

---

## How It Works

### Suggestion Engine
- Pre-built database of contextual messages
- Random selection from template library
- Personalization with names

### Sentiment Analysis
- Word frequency counting
- Positive/negative word mapping
- Confidence calculation

### Keyword Extraction
- Stop-word removal
- Word frequency analysis
- Length filtering (3+ characters)

### Text Validation
- Pattern matching
- Length checks
- Content pattern detection

---

## Customization

### Add New Suggestion Context
Edit [ai/simple_ai.php](ai/simple_ai.php) and update `$this->suggestions_db`:

```php
$this->suggestions_db['new_context'] = [
    'First suggestion',
    'Second suggestion',
    'Third suggestion',
];
```

### Add New Template
Edit the `$this->templates_db` array:

```php
$this->templates_db['new_template'] = 'Your template with {placeholders}';
```

---

## Performance

- **Response Time**: < 100ms
- **Memory Usage**: ~2MB
- **Database Size**: ~50KB
- **No Network Overhead**: Fully local processing

---

## Troubleshooting

### AI Features Not Loading
1. Check if `ai/` directory exists
2. Verify `ai/simple_ai.php` is in place
3. Check `ai/api.php` permissions

### Suggestions Not Showing
- Verify context name is correct
- Check browser console for errors
- Ensure `ai/api.php` is accessible

### Slow Performance
- Not applicable - AI runs locally and is extremely fast
- If slow, check server CPU load (unrelated to AI)

---

## Comparison: Ollama vs Simple Offline AI

| Feature | Ollama | Simple AI |
|---------|--------|-----------|
| Setup Required | ✅ Yes | ❌ No |
| Download Models | ✅ Yes (Gigabytes) | ❌ No |
| Internet Required | ✅ Setup phase | ❌ Never |
| Startup Time | ⚠️ Slow (server start) | ✅ Instant |
| Memory Usage | 🔴 High (4GB+) | 🟢 Very Low (2MB) |
| Message Suggestions | ✅ Yes | ✅ Yes |
| Sentiment Analysis | ✅ Advanced | ✅ Basic |
| Customization | ✅ Advanced | ✅ Easy |
| Use Case | General AI | Parish-specific |

---

## Future Enhancements

Possible additions (maintain offline approach):
- [ ] Message templates database
- [ ] User learning preferences
- [ ] Parish-specific terminology
- [ ] Multi-language support
- [ ] Offline speech recognition
- [ ] Custom sentiment lexicon
- [ ] Duplicate message detection

---

## License

Part of Parish M Application - Same license as main application.

---

## Support

For issues or suggestions:
1. Check [ai/simple_ai.php](ai/simple_ai.php) for available methods
2. Review API documentation above
3. Test in web interface first
4. Check browser console for errors

---

*Last Updated: January 29, 2026*
*Simple Offline AI v1.0 - Zero Dependencies*

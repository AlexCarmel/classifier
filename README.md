# Ticket Classification System

A Laravel + Vue 3 application for managing and automatically classifying support tickets using OpenAI integration.

## Features

- **Ticket Management** - Create, update, and organize support tickets
- **AI Classification** - Automatic ticket categorization using OpenAI GPT
- **Dashboard Analytics** - Visual insights into ticket distribution and status
- **Advanced Filtering** - Search and filter tickets by category, status, and content
- **Rate Limiting** - Built-in rate limiting for AI classification calls
- **Responsive Design** - Modern, mobile-friendly interface
- **Comprehensive Testing** - Full test coverage for both backend and frontend

## Technology Stack

### Backend
- **Laravel 11+** - PHP framework
- **MySQL** - Database
- **OpenAI PHP Client** - AI integration
- **PHPUnit** - Testing framework

### Frontend
- **Vue 3** - JavaScript framework (Options API)
- **Vue Router 4** - Client-side routing
- **Axios** - HTTP client
- **Plain CSS + BEM** - Styling methodology
- **Vitest + Vue Test Utils** - Testing framework

## Quick Start

### Prerequisites

- PHP 8.1+
- Composer
- Node.js 18+
- npm
- MySQL 8.0+
- OpenAI API Key (optional for AI features)

### Installation

1. **Clone the repository**
   ```bash
   git clone git@github.com:AlexCarmel/classifier.git
   cd classifier
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure your `.env` file**
   ```bash
   # Database
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=classifier
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   # OpenAI (Optional - fallback mode if disabled)
   OPENAI_API_KEY=your_openai_api_key
   OPENAI_ORGANIZATION=your_openai_organization_id
   OPENAI_CLASSIFY_ENABLED=true
   OPENAI_CLASSIFY_MAX_CALLS=60
   OPENAI_CLASSIFY_WINDOW_SECONDS=60
   ```

6. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

## Running the Application

Run to start backend application
```bash
php artisan serve
```

Visit: `http://127.0.0.1/:8000`


## Testing

### Backend Tests (PHPUnit)
```bash
# Run all backend tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# With coverage
php artisan test --coverage
```

### Frontend Tests (Vitest)
```bash
# Run all frontend tests
npm test

## API Documentation

### Authentication
Currently using session-based authentication. All endpoints require CSRF protection in production.

### Endpoints

#### Tickets
- `GET /api/tickets` - List all tickets (with filters, search, pagination)
- `POST /api/tickets` - Create a new ticket
- `GET /api/tickets/{id}` - Get ticket details
- `PATCH /api/tickets/{id}` - Update ticket
- `POST /api/tickets/{id}/classify` - Classify ticket using AI
- `GET /api/tickets/{id}/classify/status` - Get classification rate limit status

#### Categories
- `GET /api/categories` - List all categories

#### Dashboard
- `GET /api/dashboard` - Get dashboard statistics

### Request Examples

**Create Ticket:**
```bash
curl -X POST http://localhost:8000/api/tickets \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "Login Issues",
    "body": "I cannot log into my account",
    "category_id": "01HKGX7RZ8M9N0P1Q2R3S4T5U6"
  }'
```

**Classify Ticket:**
```bash
curl -X POST http://localhost:8000/api/tickets/{id}/classify \
  -H "Content-Type: application/json"
```

### AI Classification

The system uses OpenAI's API to classify tickets into categories. When `OPENAI_CLASSIFY_ENABLED=false`, it falls back to random classification with dummy data.

**Rate Limiting:** Uses Laravel's built-in `RateLimiter` to prevent API abuse.

**Fallback Mode:** When OpenAI is disabled or fails, returns random categories for testing/demo purposes.

## Assumptions & Trade-offs

### Assumptions Made

1. **Single Tenant Application**
   - Assumed single organization use case
   - No multi-tenancy or user isolation implemented
   - All users can see all tickets

2. **Authentication Strategy**
   - Used session-based authentication for simplicity
   - No role-based access control implemented
   - Assumed internal/trusted user environment

3. **OpenAI Integration**
   - Assumed OpenAI API availability and reliability
   - Single prompt strategy for all ticket types
   - No conversation context or thread management

4. **Performance Considerations**
   - Implemented basic pagination but not virtual scrolling
   - No caching layer for API responses
   - Database queries not optimized for high volume

### Trade-offs Made

1. **Complexity vs. Simplicity**
   - **Chose:** Simple, monolithic architecture
   - **Avoided:** Microservices, event-driven architecture
   - **Reason:** Faster development, easier maintenance for small-medium scale

2. **Testing Strategy**
   - **Chose:**  Unit and integration tests
   - **Avoided:** End-to-end browser testing
   - **Reason:** Better ROI for development speed vs. testing coverage

5. **AI Integration**
   - **Chose:** Direct OpenAI API calls with fallback
   - **Avoided:** Custom ML models or training
   - **Reason:** Faster time-to-market, leveraging existing AI capabilities

## What I'd Do With More Time

### Immediate Improvements

1. **Enhanced Authentication & Authorization**
   - Implement proper user registration/login system
   - Add role-based permissions (admin, agent, user)
   - JWT token authentication for API security

2. **Advanced AI Features**
   - Multiple classification models/prompts for different ticket types
   - Confidence thresholds with manual review queues
   - Learning from user corrections to improve accuracy
   - Bulk classification operations

3. **Performance Optimizations**
   - Database query optimization and indexing
   - API response caching (Redis)
   - Frontend virtual scrolling for large ticket lists
   - Image/attachment support with cloud storage

### Medium-term Enhancements

1. **User Experience Improvements**
   - Real-time notifications for ticket updates
   - Advanced search with filters and saved searches
   - Ticket templates and quick responses
   - Email notifications and integrations
   - Mobile app or PWA support

2. **Analytics & Reporting**
   - Advanced dashboard with custom date ranges
   - Export functionality (CSV, PDF reports)
   - Performance metrics for agents
   - AI accuracy tracking and analytics


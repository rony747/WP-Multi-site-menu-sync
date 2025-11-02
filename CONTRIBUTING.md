# Contributing to Avro Multisite Menu Sync

Thank you for your interest in contributing! This document provides guidelines for contributing to the project.

## Code of Conduct

- Be respectful and inclusive
- Welcome newcomers
- Focus on constructive feedback
- Collaborate openly

## How to Contribute

### Reporting Bugs

1. Check if bug already reported in issues
2. Use bug report template
3. Include:
   - WordPress version
   - PHP version
   - Steps to reproduce
   - Expected vs actual behavior
   - Error logs
   - Screenshots if applicable

### Suggesting Features

1. Check if feature already requested
2. Use feature request template
3. Explain use case
4. Describe expected behavior
5. Consider implementation impact

### Submitting Code

1. Fork the repository
2. Create feature branch: `git checkout -b feature/your-feature`
3. Make your changes
4. Follow coding standards
5. Add/update tests
6. Update documentation
7. Commit with clear messages
8. Push to your fork
9. Submit pull request

## Development Setup

```bash
# Clone your fork
git clone https://github.com/yourusername/avro-multisite-menu-sync.git
cd avro-multisite-menu-sync

# Install dependencies
composer install
npm install

# Create feature branch
git checkout -b feature/my-feature
```

## Coding Standards

### PHP
- Follow WordPress PHP Coding Standards
- Use tabs for indentation
- Document all functions
- Use type hints where possible
- Write meaningful variable names

### JavaScript
- Use ES6+ features
- Follow WordPress JavaScript standards
- Document complex logic
- Use meaningful function names

### CSS
- Follow WordPress CSS standards
- Use BEM methodology
- Mobile-first approach
- Comment complex selectors

## Testing

### Run Tests
```bash
# PHP tests
./vendor/bin/phpunit

# Code standards
./vendor/bin/phpcs includes/

# Static analysis
./vendor/bin/phpstan analyse includes/
```

### Write Tests
- Add unit tests for new features
- Maintain 80%+ coverage
- Test edge cases
- Test error conditions

## Pull Request Process

1. **Before Submitting**:
   - All tests pass
   - Code follows standards
   - Documentation updated
   - Changelog updated
   - No merge conflicts

2. **PR Description**:
   - Clear title
   - Describe changes
   - Reference related issues
   - List breaking changes
   - Add screenshots if UI changes

3. **Review Process**:
   - Maintainers review code
   - Address feedback
   - Update as needed
   - Approved PRs merged

## Commit Messages

Format: `type: description`

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Formatting
- `refactor`: Code restructuring
- `test`: Tests
- `chore`: Maintenance

**Examples**:
```
feat: add WP-CLI support
fix: resolve object ID mapping issue
docs: update API reference
test: add unit tests for logger
```

## Branch Naming

- `feature/feature-name` - New features
- `bugfix/bug-description` - Bug fixes
- `hotfix/critical-fix` - Critical fixes
- `docs/documentation-update` - Documentation

## Documentation

- Update relevant docs with code changes
- Add inline comments for complex logic
- Update API reference for new functions
- Add examples for new features
- Keep README current

## Questions?

- Check existing documentation
- Search closed issues
- Ask in discussions
- Contact maintainers

## License

By contributing, you agree that your contributions will be licensed under GPL v2 or later.

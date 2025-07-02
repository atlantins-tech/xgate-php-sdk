#!/bin/bash

# XGATE PHP SDK Git Hooks Setup Script
# Sets up pre-commit and commit-msg hooks for quality enforcement

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PROJECT_ROOT=$(git rev-parse --show-toplevel 2>/dev/null || echo "$(pwd)")
HOOKS_DIR="$PROJECT_ROOT/.git/hooks"

echo -e "${BLUE}🔧 Setting up XGATE PHP SDK Git Hooks...${NC}"
echo ""

# Check if we're in a git repository
if [ ! -d "$PROJECT_ROOT/.git" ]; then
    echo -e "${RED}❌ Error: Not in a Git repository. Please run 'git init' first.${NC}"
    exit 1
fi

# Check if hooks directory exists
if [ ! -d "$HOOKS_DIR" ]; then
    echo -e "${RED}❌ Error: Git hooks directory not found: $HOOKS_DIR${NC}"
    exit 1
fi

echo -e "${BLUE}📁 Project root: $PROJECT_ROOT${NC}"
echo -e "${BLUE}📁 Hooks directory: $HOOKS_DIR${NC}"
echo ""

# Function to backup existing hook
backup_hook() {
    local hook_name=$1
    local hook_path="$HOOKS_DIR/$hook_name"
    
    if [ -f "$hook_path" ]; then
        echo -e "${YELLOW}⚠️  Backing up existing $hook_name hook...${NC}"
        cp "$hook_path" "$hook_path.backup.$(date +%Y%m%d_%H%M%S)"
    fi
}

# Function to install hook
install_hook() {
    local hook_name=$1
    local hook_source="$PROJECT_ROOT/.git/hooks/$hook_name"
    local hook_dest="$HOOKS_DIR/$hook_name"
    
    if [ -f "$hook_source" ]; then
        echo -e "${GREEN}✅ Installing $hook_name hook...${NC}"
        chmod +x "$hook_source"
        return 0
    else
        echo -e "${RED}❌ Hook source not found: $hook_source${NC}"
        return 1
    fi
}

# Backup existing hooks
echo -e "${BLUE}1. Backing up existing hooks...${NC}"
backup_hook "pre-commit"
backup_hook "commit-msg"
echo ""

# Install pre-commit hook
echo -e "${BLUE}2. Installing pre-commit hook...${NC}"
if install_hook "pre-commit"; then
    echo -e "${GREEN}   ✅ Pre-commit hook installed successfully${NC}"
else
    echo -e "${RED}   ❌ Failed to install pre-commit hook${NC}"
    exit 1
fi
echo ""

# Install commit-msg hook
echo -e "${BLUE}3. Installing commit-msg hook...${NC}"
if install_hook "commit-msg"; then
    echo -e "${GREEN}   ✅ Commit-msg hook installed successfully${NC}"
else
    echo -e "${RED}   ❌ Failed to install commit-msg hook${NC}"
    exit 1
fi
echo ""

# Test hooks
echo -e "${BLUE}4. Testing hook installation...${NC}"

# Test pre-commit hook
if [ -x "$HOOKS_DIR/pre-commit" ]; then
    echo -e "${GREEN}   ✅ Pre-commit hook is executable${NC}"
else
    echo -e "${RED}   ❌ Pre-commit hook is not executable${NC}"
    exit 1
fi

# Test commit-msg hook
if [ -x "$HOOKS_DIR/commit-msg" ]; then
    echo -e "${GREEN}   ✅ Commit-msg hook is executable${NC}"
else
    echo -e "${RED}   ❌ Commit-msg hook is not executable${NC}"
    exit 1
fi
echo ""

# Verify dependencies
echo -e "${BLUE}5. Verifying dependencies...${NC}"

# Check if composer dependencies are installed
if [ ! -d "$PROJECT_ROOT/vendor" ]; then
    echo -e "${YELLOW}⚠️  Composer dependencies not found. Running 'composer install'...${NC}"
    cd "$PROJECT_ROOT"
    composer install --no-dev --optimize-autoloader
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}   ✅ Composer dependencies installed${NC}"
    else
        echo -e "${RED}   ❌ Failed to install composer dependencies${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}   ✅ Composer dependencies found${NC}"
fi

# Check for required tools
REQUIRED_TOOLS=("php" "composer")
for tool in "${REQUIRED_TOOLS[@]}"; do
    if command -v "$tool" >/dev/null 2>&1; then
        echo -e "${GREEN}   ✅ $tool is available${NC}"
    else
        echo -e "${RED}   ❌ $tool is not available${NC}"
        exit 1
    fi
done

# Check for quality tools
QUALITY_TOOLS=("$PROJECT_ROOT/vendor/bin/phpstan" "$PROJECT_ROOT/vendor/bin/php-cs-fixer" "$PROJECT_ROOT/vendor/bin/phpunit")
for tool in "${QUALITY_TOOLS[@]}"; do
    if [ -f "$tool" ]; then
        echo -e "${GREEN}   ✅ $(basename $tool) is available${NC}"
    else
        echo -e "${YELLOW}   ⚠️  $(basename $tool) not found${NC}"
    fi
done
echo ""

# Configuration summary
echo -e "${BLUE}6. Configuration summary...${NC}"
echo -e "${GREEN}   📋 Hooks installed:${NC}"
echo -e "      • pre-commit: Quality checks before commit"
echo -e "      • commit-msg: Conventional commit format validation"
echo ""
echo -e "${GREEN}   🔧 Quality tools configured:${NC}"
echo -e "      • PHPStan: Static analysis (Level 8)"
echo -e "      • PHP CS Fixer: Code style enforcement"
echo -e "      • PHPUnit: Unit testing (if available)"
echo ""
echo -e "${GREEN}   📖 Documentation standards:${NC}"
echo -e "      • PHPDoc validation"
echo -e "      • Missing documentation warnings"
echo ""

# Usage instructions
echo -e "${BLUE}7. Usage instructions...${NC}"
echo -e "${GREEN}   🚀 Normal workflow:${NC}"
echo -e "      1. Make your changes"
echo -e "      2. Stage files: ${YELLOW}git add .${NC}"
echo -e "      3. Commit: ${YELLOW}git commit -m 'feat(scope): description'${NC}"
echo -e "      4. Hooks will run automatically"
echo ""
echo -e "${GREEN}   🔧 Manual quality checks:${NC}"
echo -e "      • Run PHPStan: ${YELLOW}composer run phpstan${NC}"
echo -e "      • Fix code style: ${YELLOW}composer run cs-fix${NC}"
echo -e "      • Run tests: ${YELLOW}composer run test${NC}"
echo -e "      • Check docs: ${YELLOW}composer run docs-check${NC}"
echo ""
echo -e "${GREEN}   🚫 Skip hooks (emergency only):${NC}"
echo -e "      • Skip pre-commit: ${YELLOW}git commit --no-verify${NC}"
echo -e "      • Skip tests: ${YELLOW}SKIP_TESTS=1 git commit${NC}"
echo ""
echo -e "${GREEN}   📚 Documentation:${NC}"
echo -e "      • Standards: ${YELLOW}.taskmaster/docs/DOCUMENTATION_STANDARDS.md${NC}"
echo -e "      • Templates: ${YELLOW}.taskmaster/templates/docblocks/${NC}"
echo -e "      • IDE Setup: ${YELLOW}.taskmaster/docs/IDE_SETUP.md${NC}"
echo ""

# Success message
echo -e "${GREEN}🎉 Git hooks setup completed successfully!${NC}"
echo ""
echo -e "${BLUE}💡 Tips:${NC}"
echo -e "   • Use conventional commit format: ${YELLOW}type(scope): description${NC}"
echo -e "   • Run quality checks before committing: ${YELLOW}composer run quality${NC}"
echo -e "   • Check documentation standards in ${YELLOW}.taskmaster/docs/${NC}"
echo -e "   • Use IDE templates from ${YELLOW}.taskmaster/templates/${NC}"
echo ""
echo -e "${GREEN}Happy coding! 🚀${NC}" 
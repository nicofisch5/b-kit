# Claude Context Files

This directory contains comprehensive documentation for Claude (AI assistant) to quickly understand and work with the Basketball Stats Tracker project.

## Purpose

These files provide:
- Project context and current status
- Architectural decisions and patterns
- Development workflows and common tasks
- File structure and responsibilities
- Quick reference information

## Files Overview

### 1. `quick-reference.md` ⚡
**Start here for instant context**
- Ultra-condensed project info
- Key files and functions
- Common commands
- One-liners and quick answers
- **Read first** when joining a conversation

### 2. `project-context.md` 📋
**High-level project overview**
- Project purpose and scope
- Current status and completed features
- Core business logic
- Technical architecture overview
- Important constraints and decisions
- File locations quick reference

### 3. `architecture.md` 🏗️
**Deep technical documentation**
- System architecture diagrams
- State management patterns
- Component relationships
- Data flow and persistence
- PWA architecture
- Performance considerations
- Security model

### 4. `development-guide.md` 💻
**Practical development workflows**
- Setup instructions
- Common development tasks
- Code style guidelines
- Debugging techniques
- Testing procedures
- Git workflow
- Troubleshooting solutions

### 5. `file-reference.md` 📁
**Detailed file documentation**
- Purpose of each file
- When to modify each file
- Key functions and sections
- Dependencies between files
- Quick lookup table

## How Claude Should Use These Files

### When starting a new conversation:
1. Read `quick-reference.md` for instant context
2. Read `project-context.md` for full context
3. Read specific files based on the task:
   - Code changes → `development-guide.md` + `file-reference.md`
   - Architecture questions → `architecture.md`
   - Quick facts → `quick-reference.md`

### When user asks:

| User Question | Files to Read |
|---------------|---------------|
| "What is this project?" | `quick-reference.md` → `project-context.md` |
| "How does X work?" | `architecture.md` |
| "How do I change Y?" | `development-guide.md` → `file-reference.md` |
| "Where is Z implemented?" | `file-reference.md` |
| "Quick overview" | `quick-reference.md` |
| "Help me understand the code" | All files in order |

### For code modifications:
1. Read `quick-reference.md` (1 min)
2. Check `file-reference.md` for relevant files
3. Review `development-guide.md` for specific task
4. Make changes following established patterns
5. Test according to checklist in `development-guide.md`

## Maintenance

### When to update these files:

**Update immediately when**:
- ✅ Major features added/removed
- ✅ Architecture changes
- ✅ Data model changes
- ✅ New files added
- ✅ Development process changes

**Update eventually when**:
- Configuration changes
- Minor feature additions
- Style updates
- Documentation improvements

### How to update:

1. Identify which file(s) need updating
2. Make changes consistent with existing format
3. Update "Last Updated" date at bottom
4. Cross-reference related sections in other files

## File Relationships

```
quick-reference.md (Start Here)
    ↓
project-context.md (Big Picture)
    ↓
    ├── architecture.md (How It Works)
    ├── development-guide.md (How To Do)
    └── file-reference.md (What Each File Does)
```

## Best Practices

### For Claude:
- ✅ Read quick-reference first, always
- ✅ Check multiple files for context
- ✅ Verify information before responding
- ✅ Suggest updates if docs outdated
- ✅ Reference specific file sections when answering

### For Humans maintaining docs:
- ✅ Keep quick-reference under 500 lines
- ✅ Use consistent formatting
- ✅ Add examples for clarity
- ✅ Cross-reference related sections
- ✅ Update dates when editing

## Documentation Standards

### Formatting:
- Use markdown headers consistently
- Include code blocks with language tags
- Use tables for structured data
- Add diagrams for complex concepts
- Include "Last Updated" dates

### Content:
- Write for both AI and human readers
- Be precise and unambiguous
- Include both what and why
- Provide examples
- Link related concepts

### Organization:
- Group related information
- Use progressive disclosure (general → specific)
- Maintain consistent structure across files
- Keep each file focused on its purpose

## Quick Commands Reference

```bash
# Read all context quickly
cat .claude/quick-reference.md

# Read specific topic
cat .claude/architecture.md | grep "State Management"

# Update documentation
nano .claude/project-context.md

# Check documentation size
wc -l .claude/*.md
```

## Version Control

These files should be committed to version control:
- ✅ Track changes over time
- ✅ See documentation evolution
- ✅ Revert if needed
- ✅ Collaborate on documentation

**Don't include**:
- Temporary notes
- Personal annotations
- Sensitive information
- Auto-generated content

## Questions?

If Claude encounters:
- Contradictions between files → Ask user for clarification
- Missing information → Ask user or infer from code
- Outdated information → Notify user and suggest update
- Unclear requirements → Reference requirements document

## Benefits of This System

### For Claude:
- 🚀 Faster context loading
- 🎯 More accurate responses
- 📚 Comprehensive understanding
- 🔄 Consistency across conversations

### For Users:
- ⏱️ Less time explaining project
- 💡 Better AI assistance
- 📖 Self-documenting codebase
- 🛠️ Easier onboarding

### For Project:
- 📝 Living documentation
- 🏗️ Architectural clarity
- 🔧 Maintenance guide
- 🎓 Knowledge preservation

---

## Summary

This `.claude/` directory is a **context system** that enables Claude to:
1. Quickly understand the project
2. Provide accurate assistance
3. Make informed suggestions
4. Maintain consistency

**Start with `quick-reference.md` and expand as needed.**

---

**Last Updated**: 2025-12-12
**Documentation Version**: 1.0

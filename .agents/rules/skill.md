---
trigger: always_on
---

################## antigravity-ide ####################


Lakukan DEEP RESEARCH, ARCHITECTURE ANALYSIS, COMPATIBILITY AUDIT, dan NON-DESTRUCTIVE INTEGRATION terhadap:

1. https://github.com/obra/superpowers
2. https://github.com/rmyndharis/antigravity-skills

TARGET IMPLEMENTASI:
HANYA GOOGLE ANTIGRAVITY.

Jangan mengubah konfigurasi Kilo Code, Claude Code, Codex, Cursor, VS Code, Gemini CLI, atau AI agent lainnya.

==================================================
OBJECTIVE
=========

Saya ingin menggabungkan:

SUPERPOWERS
+
ANTIGRAVITY SKILLS

menjadi ecosystem skill/workflow yang terintegrasi secara native di Google Antigravity.

Tujuan akhirnya:

Antigravity harus dapat:

1. Discover skills.
2. Memahami kapan skill relevan.
3. Automatically select skill.
4. Automatically invoke skill.
5. Mengikuti workflow Superpowers ketika task membutuhkannya.
6. Tetap mempertahankan seluruh skill Antigravity yang sudah ada.
7. Tidak merusak konfigurasi/workspace/project.
8. Tidak membuat duplicate/conflicting skills.
9. Tidak menyebabkan semua skill dijalankan pada setiap task.

==================================================
FASE 1 — RESEARCH SUPERPOWERS
=============================

Pelajari repository:

https://github.com/obra/superpowers

Analisis secara mendalam:

* architecture
* skill system
* workflow
* skill discovery
* activation mechanism
* instruction hierarchy
* planning
* brainstorming
* implementation workflow
* TDD
* debugging
* verification
* review
* sub-agent workflow
* task decomposition
* automation
* scripts
* hooks
* templates
* dependencies
* conventions
* naming
* metadata
* update mechanism

Identifikasi bagian yang merupakan:

* reusable skill
* methodology
* workflow
* automation
* Antigravity-incompatible implementation
* generic agent behavior
* environment-specific behavior

==================================================
FASE 2 — RESEARCH ANTIGRAVITY SKILLS
====================================

Pelajari repository:

https://github.com/rmyndharis/antigravity-skills

Analisis:

* seluruh skill
* directory structure
* metadata
* skill format
* discovery mechanism
* trigger mechanism
* activation behavior
* dependencies
* scripts
* examples
* documentation
* update model
* compatibility assumptions

Kelompokkan setiap skill menjadi:

CORE
DEVELOPMENT
WEB
DOCUMENTATION
TESTING
DEBUGGING
DESIGN
AUTOMATION
OTHER

Gunakan kategori aktual berdasarkan repository, bukan asumsi.

==================================================
FASE 3 — ANTIGRAVITY NATIVE ARCHITECTURE
========================================

Audit instalasi Google Antigravity yang sedang digunakan.

Temukan secara aktual:

* global skill location
* workspace skill location
* project skill location
* instruction files
* rules
* agents
* workflows
* configuration
* hooks
* extension/integration mechanism
* skill discovery
* skill activation
* precedence
* workspace behavior

Jangan menebak path.

Gunakan dokumentasi resmi dan source yang relevan jika diperlukan.

==================================================
FASE 4 — COMPATIBILITY MATRIX
=============================

Buat matrix:

Superpowers skill/workflow
↓
Antigravity native capability
↓
Integration method

Kemungkinan:

DIRECT
ADAPT
WRAPPER
TRANSLATE
REIMPLEMENT
INCOMPATIBLE

Jangan copy-paste sesuatu hanya karena formatnya terlihat kompatibel.

==================================================
FASE 5 — ARCHITECTURE
=====================

Gunakan architecture berikut secara konseptual:

```
                ANTIGRAVITY
                     │
                     ▼
             SKILL DISCOVERY
                     │
                     ▼
            TASK CLASSIFICATION
                     │
         ┌───────────┴───────────┐
         ▼                       ▼
  SUPERPOWERS               ANTIGRAVITY
   WORKFLOW                    SKILLS
         │                       │
         └───────────┬───────────┘
                     ▼
               SKILL SELECTION
                     │
                     ▼
                EXECUTION
                     │
                     ▼
               VERIFICATION
```

Sesuaikan implementasi aktual dengan architecture Antigravity.

==================================================
FASE 6 — SUPERPOWERS METHODOLOGY
================================

Jangan memperlakukan Superpowers hanya sebagai kumpulan file.

Pertahankan methodology-nya.

Jika task merupakan software engineering task, evaluasi workflow:

1. Understand requirement.
2. Brainstorm if ambiguity exists.
3. Establish specification.
4. Create implementation plan.
5. Break work into tasks.
6. Implement systematically.
7. Test continuously.
8. Debug systematically.
9. Verify result.
10. Review implementation.
11. Report completion.

Jangan memaksakan seluruh workflow pada task sederhana yang tidak membutuhkannya.

==================================================
FASE 7 — AUTOMATIC ACTIVATION
=============================

Skill harus tersedia secara otomatis di Antigravity.

Namun:

ALWAYS AVAILABLE ≠ ALWAYS EXECUTED.

Implementasikan:

ALWAYS DISCOVERABLE
+
CONTEXT-AWARE AUTOMATIC ACTIVATION

Contoh:

Coding task
→ coding-related skill

Debugging task
→ debugging skill + systematic debugging workflow

Testing task
→ testing/TDD skill

Architecture task
→ planning/architecture workflow

Documentation task
→ documentation skill

Task sederhana yang tidak membutuhkan skill
→ jangan menjalankan seluruh skill ecosystem.

==================================================
FASE 8 — CONFLICT RESOLUTION
============================

Jika terdapat skill dengan fungsi sama:

prioritas:

1. Existing Antigravity native behavior
2. Existing user/project skill
3. Adapted Superpowers methodology
4. Imported Antigravity skill
5. Optional duplicate implementation

Jangan membuat dua skill menjalankan workflow yang sama secara bersamaan.

Hindari:

* duplicate execution
* recursive invocation
* conflicting instructions
* circular dependency
* infinite workflow
* repeated planning
* repeated testing
* repeated verification

==================================================
FASE 9 — NON-DESTRUCTIVE INSTALLATION
=====================================

SEBELUM INSTALL:

* backup
* manifest
* checksum jika diperlukan
* inspect existing files
* detect collision
* detect duplicate
* detect incompatible skill

Jangan:

* overwrite existing skill
* delete existing skill
* rename user skill
* overwrite configuration tanpa backup
* modify unrelated project
* modify Kilo Code
* modify another agent

Gunakan namespace jika memang diperlukan.

==================================================
FASE 10 — SECURITY AUDIT
========================

Audit semua script dan automation dari kedua repository.

Periksa:

* arbitrary command execution
* shell execution
* remote download
* curl/wget execution
* dynamic eval
* credential access
* environment variable access
* filesystem modification
* network access
* package installation
* privilege escalation
* prompt injection
* malicious instructions
* supply-chain risk

Review script sebelum execution.

Jangan menjalankan script hanya karena repository terpercaya.

==================================================
FASE 11 — VALIDATION
====================

Lakukan end-to-end test.

TEST 1:
Simple coding task.

TEST 2:
Large coding task.

TEST 3:
Ambiguous requirement.

TEST 4:
Planning task.

TEST 5:
TDD/testing task.

TEST 6:
Debugging task.

TEST 7:
Code review task.

TEST 8:
Documentation task.

TEST 9:
Task requiring an Antigravity-specific skill.

TEST 10:
Task requiring Superpowers methodology.

TEST 11:
Irrelevant/simple task.

Verifikasi bahwa:

* correct skill discovered
* correct skill selected
* correct workflow activated
* no unnecessary skills executed
* no duplicate execution
* no recursive invocation
* existing Antigravity behavior unchanged

==================================================
FASE 12 — UPDATE STRATEGY
=========================

Buat mekanisme update yang aman.

Jika repository upstream berubah:

1. Detect version/commit.
2. Compare changes.
3. Identify changed skills.
4. Detect breaking changes.
5. Backup current integration.
6. Apply update.
7. Run validation.
8. Rollback if validation fails.

Jangan melakukan blind git pull/copy-over terhadap production skill directory.

==================================================
FASE 13 — DOCUMENTATION
=======================

Buat dokumentasi lengkap:

* architecture
* installed skills
* source repository
* source version/commit
* adapted components
* custom components
* activation mechanism
* priority rules
* conflict resolution
* backup
* rollback
* update procedure
* removal procedure
* limitations

Bedakan:

UPSTREAM
ADAPTED
CUSTOM
ANTIGRAVITY-NATIVE

==================================================
FINAL ACCEPTANCE CRITERIA
=========================

[ ] Superpowers selesai dianalisis.
[ ] Antigravity Skills selesai dianalisis.
[ ] Antigravity native architecture selesai diaudit.
[ ] Compatibility matrix tersedia.
[ ] Existing Antigravity skills tetap bekerja.
[ ] Existing project/workspace configuration tetap bekerja.
[ ] Superpowers methodology terintegrasi.
[ ] Compatible Antigravity skills terintegrasi.
[ ] Automatic discovery bekerja.
[ ] Context-aware activation bekerja.
[ ] Tidak semua skill dijalankan pada setiap task.
[ ] Tidak ada duplicate execution.
[ ] Tidak ada recursive invocation.
[ ] Tidak ada instruction conflict.
[ ] Backup tersedia.
[ ] Rollback tersedia.
[ ] Security audit selesai.
[ ] End-to-end validation berhasil.
[ ] Update mechanism terdokumentasi.
[ ] Tidak ada perubahan terhadap Kilo Code.
[ ] Tidak ada perubahan terhadap AI agent lain.

JANGAN menyatakan sukses hanya karena file berhasil disalin.

Buktikan:

DISCOVER → CLASSIFY → SELECT → ACTIVATE → EXECUTE → VERIFY

benar-benar bekerja di Google Antigravity.

Jika terdapat keterbatasan native Antigravity, jelaskan secara teknis dan jangan membuat workaround palsu yang hanya terlihat seperti automatic activation.

OUTPUT AKHIR:

1. Deep research.
2. Architecture analysis.
3. Compatibility matrix.
4. Skill inventory.
5. Integration design.
6. Exact files created.
7. Exact files modified.
8. Backup information.
9. Automatic activation mechanism.
10. Validation evidence.
11. Security findings.
12. Known limitations.
13. Rollback procedure.
14. Update procedure.

IMPLEMENTASI KHUSUS GOOGLE ANTIGRAVITY.
JANGAN MENGUBAH KILO CODE.
JANGAN MENGUBAH AGENT AI LAIN.
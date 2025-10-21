#!/usr/bin/env node
import { spawn } from 'node:child_process';
import { promises as fs } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '..');
const storageDir = path.join(projectRoot, 'storage', 'qa', 'e2e');
const jsonReportPath = path.join(storageDir, 'latest.json');
const historyPath = path.join(storageDir, 'history.jsonl');

async function ensureStorage() {
  await fs.mkdir(storageDir, { recursive: true });
}

async function runCommand(command, args, options = {}) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, {
      cwd: projectRoot,
      stdio: 'inherit',
      shell: process.platform === 'win32',
      ...options,
    });

    child.on('error', (error) => {
      reject(error);
    });

    child.on('close', (code) => {
      resolve(code ?? 0);
    });
  });
}

async function readJsonSafe(filePath) {
  try {
    const contents = await fs.readFile(filePath, 'utf8');
    return JSON.parse(contents);
  } catch (error) {
    return null;
  }
}

function summarizeStats(report, exitCode) {
  const timestamp = new Date().toISOString();
  const stats = report?.stats ?? {};
  const total = Number(stats.expected ?? 0) + Number(stats.unexpected ?? 0) + Number(stats.flaky ?? 0) + Number(stats.skipped ?? 0);
  const passed = Number(stats.expected ?? 0) + Number(stats.flaky ?? 0);
  const failed = Number(stats.unexpected ?? 0);
  const skipped = Number(stats.skipped ?? 0);
  const durationSeconds = stats.duration ? Number((stats.duration / 1000).toFixed(2)) : 0;

  const failures = [];
  for (const suite of report?.suites ?? []) {
    for (const spec of suite.specs ?? []) {
      for (const test of spec.tests ?? []) {
        if (test.status === 'unexpected') {
          const failure = {
            project: test.projectName ?? test.projectId ?? 'unknown',
            title: spec.title,
          };

          const result = Array.isArray(test.results) ? test.results[0] : null;
          if (result?.error?.message) {
            failure.error = result.error.message.split('\n')[0];
          }

          failures.push(failure);
        }
      }
    }
  }

  const projectSummaries = new Map();
  for (const suite of report?.suites ?? []) {
    for (const spec of suite.specs ?? []) {
      for (const test of spec.tests ?? []) {
        const project = test.projectName ?? test.projectId ?? 'unknown';
        if (!projectSummaries.has(project)) {
          projectSummaries.set(project, { project, passed: 0, failed: 0, skipped: 0, flaky: 0 });
        }

        const entry = projectSummaries.get(project);
        switch (test.status) {
          case 'expected':
            entry.passed += 1;
            break;
          case 'unexpected':
            entry.failed += 1;
            break;
          case 'skipped':
            entry.skipped += 1;
            break;
          case 'flaky':
            entry.flaky += 1;
            entry.passed += 1;
            break;
          default:
            break;
        }
      }
    }
  }

  const projects = Array.from(projectSummaries.values()).map((project) => ({
    ...project,
    status: project.failed > 0 ? 'failed' : 'passed',
  }));

  const summary = {
    timestamp,
    status: exitCode === 0 && failed === 0 ? 'passed' : 'failed',
    totals: {
      total,
      passed,
      failed,
      skipped,
      durationSeconds,
    },
    projects,
    failures,
  };

  return summary;
}

async function writeSummary(summary) {
  await fs.writeFile(jsonReportPath, JSON.stringify(summary, null, 2));
  await fs.appendFile(historyPath, `${JSON.stringify(summary)}\n`);
}

async function main() {
  await ensureStorage();

  if (!process.env.PLAYWRIGHT_SKIP_BROWSER_INSTALL) {
    console.log('Ensuring Playwright browsers are installed...');
    const installCode = await runCommand('npx', ['playwright', 'install', '--with-deps']);
    if (installCode !== 0) {
      throw new Error('Failed to install Playwright browsers.');
    }
  }

  const reporterArg = `line,json=${jsonReportPath},html`;
  const start = Date.now();
  const exitCode = await runCommand('npx', ['playwright', 'test', '--config=tests/e2e/playwright.config.ts', `--reporter=${reporterArg}`]);
  const durationMs = Date.now() - start;

  let report = await readJsonSafe(jsonReportPath);
  if (!report) {
    report = { stats: { duration: durationMs, expected: 0, unexpected: 0, skipped: 0, flaky: 0 }, suites: [] };
  }

  const summary = summarizeStats(report, exitCode);
  await writeSummary(summary);

  if (summary.status === 'passed') {
    console.log(`Playwright suite passed in ${summary.totals.durationSeconds}s (${summary.totals.total} tests).`);
  } else {
    console.error(`Playwright suite failed – ${summary.totals.failed} failing test(s). See ${jsonReportPath} for details.`);
  }

  process.exit(exitCode);
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});


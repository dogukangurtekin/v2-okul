/*
 * Sayfa testleri için Python çalıştırıcı: GERÇEK Pyodide'i Node içinde çalıştırır (WebAssembly, sistem Python'u gerekmez).
 * Sayfadaki `window.PyRunner` yerine enjekte edilir; Web Worker katmanını (pyWorkerSource) pyworker.mjs ayrıca sınar.
 */
let pyPromise = null;
export const getPyodide = () => (pyPromise ??= import('pyodide').then(m => m.loadPyodide()));

export function makeRunner() {
  const dec = new TextDecoder();
  const runner = {
    calls: 0,
    async run(code, stdin) {
      runner.calls++;
      const py = await getPyodide();
      const so = [], se = [];
      const lines = String(stdin || '').split(/\r?\n/); if (lines.length && lines[lines.length - 1] === '') lines.pop();
      py.setStdin({ stdin: () => (lines.length ? lines.shift() : null) });
      py.setStdout({ raw: b => so.push(b & 255) });
      py.setStderr({ raw: b => se.push(b & 255) });
      const g = py.globals.get('dict')();
      let error = '';
      try { await py.runPythonAsync(code, { globals: g }); } catch (e) { error = String(e.message || e); }
      try { py.runPython('import sys\nsys.stdout.flush()\nsys.stderr.flush()'); } catch {}
      g.destroy();
      return { ok: !error, stdout: dec.decode(new Uint8Array(so)), stderr: dec.decode(new Uint8Array(se)), error };
    },
  };
  return runner;
}

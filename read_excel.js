import XLSX from 'xlsx';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const filePath = path.join(__dirname, 'PT INDOBOX UTAMA JAYA - GAJI NOVEMBER 2025.xlsx');
const wb = XLSX.readFile(filePath);

let output = '';

function log(s) {
    output += s + '\n';
}

log('=== SHEET NAMES ===');
log(JSON.stringify(wb.SheetNames));

wb.SheetNames.forEach(sheetName => {
    const ws = wb.Sheets[sheetName];
    const range = XLSX.utils.decode_range(ws['!ref'] || 'A1');
    log(`\n${'='.repeat(60)}`);
    log(`SHEET: ${sheetName}`);
    log(`Range: ${ws['!ref']}`);
    log(`Rows: ${range.e.r + 1}, Cols: ${range.e.c + 1}`);
    log('='.repeat(60));
    
    const data = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
    
    // Print ALL rows  
    for (let i = 0; i < data.length; i++) {
        const row = data[i];
        const nonEmpty = row.filter(c => c !== '' && c !== null && c !== undefined);
        if (nonEmpty.length > 0) {
            // Trim trailing empty cells for readability
            let lastNonEmpty = 0;
            for (let j = row.length - 1; j >= 0; j--) {
                if (row[j] !== '' && row[j] !== null && row[j] !== undefined) {
                    lastNonEmpty = j;
                    break;
                }
            }
            const trimmedRow = row.slice(0, lastNonEmpty + 1);
            log(`Row ${i+1}: ${JSON.stringify(trimmedRow)}`);
        }
    }
});

fs.writeFileSync(path.join(__dirname, 'excel_analysis.txt'), output, 'utf8');
console.log('Output written to excel_analysis.txt');
console.log(`Total size: ${output.length} chars`);

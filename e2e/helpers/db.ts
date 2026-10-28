import { execSync } from 'child_process';
import path from 'path';

export interface SeededTicket {
  ticket_id: string;
  tracking_code: string;
  status: string;
  order_number: string;
  customer_dni: string;
}

const rootDir = process.cwd();

export function cleanDatabase(): void {
  const scriptPath = path.join(rootDir, 'e2e/helpers/clean-db.php');
  execSync(`php "${scriptPath}"`, { stdio: 'pipe' });
}

export function seedTicket(status: string = 'received'): SeededTicket {
  cleanDatabase();
  const scriptPath = path.join(rootDir, 'e2e/helpers/seed-ticket.php');
  const output = execSync(`php "${scriptPath}" ${status}`, { encoding: 'utf-8' });
  return JSON.parse(output.trim());
}

export function seedActiveOrderFixture(): void {
  execSync('php artisan db:seed --class=ExternalOrderCacheSeeder', { cwd: rootDir, stdio: 'pipe' });
}

import { requireStaff } from "@/lib/auth";
import { getSettings } from "@/lib/settings";
import { SettingsForm } from "@/components/admin/SettingsForm";
import { PageHead } from "@/components/admin/ui";

export default async function SettingsPage() {
  await requireStaff(["ADMIN"]);
  return <><PageHead title="Settings" /><SettingsForm init={await getSettings()} /></>;
}

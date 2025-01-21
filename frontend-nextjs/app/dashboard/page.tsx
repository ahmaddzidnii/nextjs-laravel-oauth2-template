import { LogoutButton } from "@/features/auth/components/LogoutButton";
import { getAuth } from "@/helpers/getAuth";

export default async function Dashboard() {
  const auth = await getAuth();
  return (
    <div className="space-y-5">
      <p>Hello {auth?.user?.email}</p>
      <img
        className="rounded-md"
        src={auth?.user?.avatar}
        alt={auth?.user?.name}
      />
      <LogoutButton />
    </div>
  );
}

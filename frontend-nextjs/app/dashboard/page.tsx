import { Metadata } from "next";

import { getAuth } from "@/helpers/getAuth";
import { LogoutButton } from "@/features/auth/components/LogoutButton";
import { ClientSideAuth } from "@/app/components/ClientSideAuth";

export async function generateMetadata(): Promise<Metadata> {
  const auth = await getAuth();

  return {
    title: `${auth.user?.username} Dashboard`,
  };
}

export default async function DashboardPage() {
  const { isAuthenticated, user, getAccessToken } = await getAuth();
  return (
    <div className="w-full min-h-screen flex flex-col items-center justify-center">
      <ClientSideAuth />
      <div className="w-[768px] bg-gray-200 flex flex-col justify-center items-center rounded-lg p-4 shadow-sm">
        <h1 className="text-4xl font-bold">Server Side Rendering</h1>
        <pre className="mt-5 overflow-x-auto max-w-full">
          {JSON.stringify({ isAuthenticated, accsessToken: await getAccessToken(), user }, null, 2)}
        </pre>
      </div>
      <LogoutButton />
    </div>
  );
}

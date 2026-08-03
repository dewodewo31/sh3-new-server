// app/sponsors/page.js
import Container from "@/src/components/Container";
import { SponsorList } from "../../components/SponsorList";
import BatikOverlay from "@/src/components/BatikOverlay";

export default function SponsorsPage() {
  return (
    <Container className="flex flex-col gap-y-4 w-full">
      <div className="bg-linear-to-br from-primary-light via-primary-light-active to-primary-light relative">
        <BatikOverlay />
        <div className="mt-8 max-w-306 mx-auto  relative">
          <div className="text-5xl font-bold font-young text-center mt-24">
            Sponsor Komunitas kami!
          </div>
          <SponsorList />
        </div>
      </div>
    </Container>
  );
}

export default function GridBackground() {
  return (
    <div 
      className="pointer-events-none fixed inset-0 z-0 opacity-[0.15]"
      style={{
        backgroundImage: `url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h40v40H0V0zm39 39V1H1v38h38z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E")`,
        backgroundSize: "40px 40px",
        maskImage: "linear-gradient(to bottom, black 20%, transparent 80%)",
        WebkitMaskImage: "linear-gradient(to bottom, black 20%, transparent 80%)",
      }}
    />
  );
}
